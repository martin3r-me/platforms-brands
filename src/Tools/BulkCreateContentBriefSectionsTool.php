<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Create: mehrere Content Brief Sections in einem Call anlegen.
 *
 * Sinn: reduziert Toolcalls/Iterationen für schnelle Gliederungserstellung.
 * REST-Idee: POST /brands/content_brief_sections/bulk
 */
class BulkCreateContentBriefSectionsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_sections.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_brief_sections/bulk - Erstellt mehrere Gliederungsabschnitte (Outline Sections) in einem Request. Body MUSS {sections:[{content_brief_id,heading,...}], defaults?} enthalten. Nützlich für schnelle Gliederungserstellung.';
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
                        'content_brief_id' => ['type' => 'integer'],
                        'heading_level' => ['type' => 'string', 'enum' => ['h2', 'h3', 'h4']],
                    ],
                ],
                'sections' => [
                    'type' => 'array',
                    'description' => 'Liste von Sections. Jedes Element entspricht den Parametern von brands.content_brief_sections.POST.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'content_brief_id' => ['type' => 'integer'],
                            'heading' => ['type' => 'string'],
                            'heading_level' => ['type' => 'string', 'enum' => ['h2', 'h3', 'h4']],
                            'description' => ['type' => 'string'],
                            'target_keywords' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'notes' => ['type' => 'string'],
                            'order' => ['type' => 'integer'],
                        ],
                        'required' => ['heading'],
                    ],
                ],
            ],
            'required' => ['sections'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $items = $arguments['sections'] ?? null;
            if (!is_array($items) || empty($items)) {
                return ToolResult::error('INVALID_ARGUMENT', 'sections muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool) ($arguments['atomic'] ?? false);
            $singleTool = new CreateContentBriefSectionTool();

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
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der Content Brief Sections: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'content_brief_sections', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
