<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Delete: mehrere Intake Board Blocks in einem Call löschen.
 *
 * Sinn: reduziert Toolcalls/Iterationen (LLM kann 10+ Deletes in einem Schritt erledigen).
 * REST-Idee: DELETE /brands/intake_board_blocks/bulk
 */
class BulkRemoveIntakeBoardBlocksTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_board_blocks.BULK_DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/intake_board_blocks/bulk - Löscht mehrere Intake Board Blocks in einem Request. Nützlich für Batch-Operationen ohne viele Toolcalls.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden alle Deletes in einer DB-Transaktion ausgeführt (bei einem Fehler wird alles zurückgerollt). Standard: false.',
                ],
                'board_block_ids' => [
                    'type' => 'array',
                    'description' => 'Liste von Intake Board Block-IDs, die gelöscht werden sollen.',
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
            ],
            'required' => ['board_block_ids'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $boardBlockIds = $arguments['board_block_ids'] ?? null;
            if (!is_array($boardBlockIds) || empty($boardBlockIds)) {
                return ToolResult::error('INVALID_ARGUMENT', 'board_block_ids muss ein nicht-leeres Array sein.');
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new DeleteIntakeBoardBlockTool();

            $run = function() use ($boardBlockIds, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($boardBlockIds as $idx => $id) {
                    if (!is_int($id) && !is_numeric($id)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Board Block-ID muss eine Ganzzahl sein.'],
                        ];
                        continue;
                    }

                    $res = $singleTool->execute(['board_block_id' => (int)$id], $context);
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
                        'requested' => count($boardBlockIds),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Löschen der Intake Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'intake_board_blocks', 'bulk', 'batch', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
