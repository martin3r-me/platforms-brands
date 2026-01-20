<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Create: mehrere Content Board Blocks in einem Call anlegen.
 *
 * Sinn: reduziert Toolcalls/Iterationen (LLM kann 10-50 Content Board Blocks in einem Schritt erstellen).
 * REST-Idee: POST /brands/content_board_blocks/bulk
 */
class BulkCreateContentBoardBlocksTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_board_blocks.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_board_blocks/bulk - Body MUSS {content_board_blocks:[{row_id,name,description?}], defaults?} enthalten. Erstellt viele Content Board Blocks (z.B. für mehrere Inhalte/Texte).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden alle Creates in einer DB-Transaktion ausgeführt (bei einem Fehler wird alles zurückgerollt). Standard: false.',
                ],
                'defaults' => [
                    'type' => 'object',
                    'description' => 'Optional: Default-Werte, die auf jedes Item angewendet werden (können pro Item überschrieben werden).',
                    'properties' => [
                        'row_id' => ['type' => 'integer'],
                        'span' => ['type' => 'integer'],
                    ],
                    'required' => [],
                ],
                'content_board_blocks' => [
                    'type' => 'array',
                    'description' => 'Liste von Content Board Blocks. Jedes Element entspricht den Parametern von brands.content_board_blocks.POST (mindestens row_id, name).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'row_id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'span' => ['type' => 'integer'],
                        ],
                        'required' => ['row_id', 'name'],
                    ],
                ],
            ],
            'required' => ['content_board_blocks'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $blocks = $arguments['content_board_blocks'] ?? null;
            if (!is_array($blocks) || empty($blocks)) {
                return ToolResult::error('INVALID_ARGUMENT', 'content_board_blocks muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new CreateContentBoardBlockTool();

            $run = function() use ($blocks, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($blocks as $idx => $b) {
                    if (!is_array($b)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Content Board Block-Item muss ein Objekt sein.'],
                        ];
                        continue;
                    }

                    // Defaults anwenden, ohne explizite Werte zu überschreiben
                    $payload = $defaults;
                    foreach ($b as $k => $v) {
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
                        'requested' => count($blocks),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der Content Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'content_board_blocks', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
