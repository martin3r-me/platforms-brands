<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Create: mehrere Content Brief Boards in einem Call anlegen.
 *
 * Sinn: reduziert Toolcalls/Iterationen (z.B. aus Cluster-Daten in einem Schritt erstellen).
 * REST-Idee: POST /brands/content_brief_boards/bulk
 */
class BulkCreateContentBriefBoardsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_boards.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_brief_boards/bulk - Erstellt mehrere Content Brief Boards in einem Request. Body MUSS {content_brief_boards:[{brand_id,name,...}], defaults?} enthalten. Nützlich für Massenanlage aus Cluster-Daten.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden alle Creates in einer DB-Transaktion ausgeführt. Standard: false.',
                ],
                'defaults' => [
                    'type' => 'object',
                    'description' => 'Optional: Default-Werte, die auf jedes Item angewendet werden (können pro Item überschrieben werden).',
                    'properties' => [
                        'brand_id' => ['type' => 'integer'],
                        'content_type' => ['type' => 'string', 'enum' => ['pillar', 'how-to', 'listicle', 'faq', 'comparison', 'deep-dive', 'guide']],
                        'search_intent' => ['type' => 'string', 'enum' => ['informational', 'commercial', 'transactional', 'navigational']],
                        'status' => ['type' => 'string', 'enum' => ['draft', 'briefed', 'in_production', 'review', 'published']],
                        'seo_board_id' => ['type' => 'integer'],
                    ],
                ],
                'content_brief_boards' => [
                    'type' => 'array',
                    'description' => 'Liste von Content Brief Boards. Jedes Element entspricht den Parametern von brands.content_brief_boards.POST.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'brand_id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'content_type' => ['type' => 'string', 'enum' => ['pillar', 'how-to', 'listicle', 'faq', 'comparison', 'deep-dive', 'guide']],
                            'search_intent' => ['type' => 'string', 'enum' => ['informational', 'commercial', 'transactional', 'navigational']],
                            'status' => ['type' => 'string', 'enum' => ['draft', 'briefed', 'in_production', 'review', 'published']],
                            'target_slug' => ['type' => 'string'],
                            'target_word_count' => ['type' => 'integer'],
                            'seo_board_id' => ['type' => 'integer'],
                        ],
                        'required' => ['name'],
                    ],
                ],
            ],
            'required' => ['content_brief_boards'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $items = $arguments['content_brief_boards'] ?? null;
            if (!is_array($items) || empty($items)) {
                return ToolResult::error('INVALID_ARGUMENT', 'content_brief_boards muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool) ($arguments['atomic'] ?? false);
            $singleTool = new CreateContentBriefBoardTool();

            $run = function () use ($items, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($items as $idx => $item) {
                    if (!is_array($item)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Item muss ein Objekt sein.'],
                        ];
                        continue;
                    }

                    $payload = $defaults;
                    foreach ($item as $k => $v) {
                        $payload[$k] = $v;
                    }

                    $res = $singleTool->execute($payload, $context);
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
                        'requested' => count($items),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn () => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der Content Brief Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'content_brief_boards', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
