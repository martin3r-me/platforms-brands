<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Create: mehrere Intake Board Blocks in einem Call anlegen.
 *
 * Sinn: reduziert Toolcalls/Iterationen (LLM kann 10-50 Intake Board Blocks in einem Schritt erstellen).
 * REST-Idee: POST /brands/intake_board_blocks/bulk
 */
class BulkAddIntakeBoardBlocksTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_board_blocks.BULK_POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/intake_board_blocks/bulk - Body MUSS {intake_board_id, blocks:[{block_definition_id, sort_order?, is_required?, is_active?}]} enthalten. Fügt viele Blocks zu einem Intake Board hinzu.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'intake_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Intake Boards (ERFORDERLICH). Nutze "brands.intake_boards.GET" um Intake Boards zu finden.'
                ],
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden alle Creates in einer DB-Transaktion ausgeführt (bei einem Fehler wird alles zurückgerollt). Standard: false.',
                ],
                'blocks' => [
                    'type' => 'array',
                    'description' => 'Liste von Blocks. Jedes Element entspricht den Parametern von brands.intake_board_blocks.POST (mindestens block_definition_id).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'block_definition_id' => ['type' => 'integer'],
                            'sort_order' => ['type' => 'integer'],
                            'is_required' => ['type' => 'boolean'],
                            'is_active' => ['type' => 'boolean'],
                        ],
                        'required' => ['block_definition_id'],
                    ],
                ],
            ],
            'required' => ['intake_board_id', 'blocks'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $intakeBoardId = $arguments['intake_board_id'] ?? null;
            if (!$intakeBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'intake_board_id ist erforderlich.');
            }

            $blocks = $arguments['blocks'] ?? null;
            if (!is_array($blocks) || empty($blocks)) {
                return ToolResult::error('INVALID_ARGUMENT', 'blocks muss ein nicht-leeres Array sein.');
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new AddIntakeBoardBlockTool();

            $run = function() use ($blocks, $intakeBoardId, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($blocks as $idx => $b) {
                    if (!is_array($b)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Block-Item muss ein Objekt sein.'],
                        ];
                        continue;
                    }

                    // intake_board_id auf jedes Item setzen
                    $payload = $b;
                    $payload['intake_board_id'] = $intakeBoardId;

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
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Hinzufügen der Intake Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'intake_board_blocks', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
