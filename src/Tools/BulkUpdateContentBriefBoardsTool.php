<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Update: mehrere Content Brief Boards in einem Call aktualisieren.
 *
 * Sinn: reduziert Toolcalls/Iterationen (z.B. Status-Updates für mehrere Briefs).
 * REST-Idee: PUT /brands/content_brief_boards/bulk
 */
class BulkUpdateContentBriefBoardsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_boards.bulk.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/content_brief_boards/bulk - Aktualisiert mehrere Content Brief Boards in einem Request. Nützlich für Batch-Operationen (z.B. Status-Updates).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden alle Updates in einer DB-Transaktion ausgeführt. Standard: false.',
                ],
                'updates' => [
                    'type' => 'array',
                    'description' => 'Liste von Updates. Jedes Element entspricht den Parametern von brands.content_brief_boards.PUT.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'content_brief_board_id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'content_type' => ['type' => 'string', 'enum' => ['pillar', 'how-to', 'listicle', 'faq', 'comparison', 'deep-dive', 'guide']],
                            'search_intent' => ['type' => 'string', 'enum' => ['informational', 'commercial', 'transactional', 'navigational']],
                            'status' => ['type' => 'string', 'enum' => ['draft', 'briefed', 'in_production', 'review', 'published']],
                            'target_slug' => ['type' => 'string'],
                            'target_word_count' => ['type' => 'integer'],
                            'seo_board_id' => ['type' => 'integer'],
                            'done' => ['type' => 'boolean'],
                        ],
                        'required' => ['content_brief_board_id'],
                    ],
                ],
            ],
            'required' => ['updates'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $updates = $arguments['updates'] ?? null;
            if (!is_array($updates) || empty($updates)) {
                return ToolResult::error('INVALID_ARGUMENT', 'updates muss ein nicht-leeres Array sein.');
            }

            $atomic = (bool) ($arguments['atomic'] ?? false);
            $singleTool = new UpdateContentBriefBoardTool();

            $run = function () use ($updates, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($updates as $idx => $u) {
                    if (!is_array($u)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Update-Item muss ein Objekt sein.'],
                        ];
                        continue;
                    }

                    $res = $singleTool->execute($u, $context);
                    if ($res->success) {
                        $okCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => true,
                            'data' => $res->data,
                        ];
                    } else {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => [
                                'code' => $res->errorCode,
                                'message' => $res->error,
                            ],
                        ];
                    }
                }

                return [
                    'results' => $results,
                    'summary' => [
                        'requested' => count($updates),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn () => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Update der Content Brief Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'content_brief_boards', 'bulk', 'batch', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
