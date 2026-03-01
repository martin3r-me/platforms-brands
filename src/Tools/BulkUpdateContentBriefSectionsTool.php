<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Update: mehrere Content Brief Sections in einem Call aktualisieren.
 *
 * Sinn: reduziert Toolcalls/Iterationen (z.B. Reordering, Heading-Level-Updates).
 * REST-Idee: PUT /brands/content_brief_sections/bulk
 */
class BulkUpdateContentBriefSectionsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_sections.bulk.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/content_brief_sections/bulk - Aktualisiert mehrere Gliederungsabschnitte (Outline Sections) in einem Request. Nützlich für Reordering oder Batch-Updates.';
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
                    'description' => 'Liste von Updates. Jedes Element entspricht den Parametern von brands.content_brief_sections.PUT.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'content_brief_section_id' => ['type' => 'integer'],
                            'heading' => ['type' => 'string'],
                            'heading_level' => ['type' => 'string', 'enum' => ['h2', 'h3', 'h4']],
                            'description' => ['type' => 'string'],
                            'target_keywords' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'notes' => ['type' => 'string'],
                            'order' => ['type' => 'integer'],
                        ],
                        'required' => ['content_brief_section_id'],
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
            $singleTool = new UpdateContentBriefSectionTool();

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
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Update der Content Brief Sections: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'content_brief_sections', 'bulk', 'batch', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
