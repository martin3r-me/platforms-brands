<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk Delete: mehrere Block-Definitionen in einem Call loeschen.
 *
 * Sinn: reduziert Toolcalls/Iterationen (LLM kann 10+ Deletes in einem Schritt erledigen).
 * REST-Idee: DELETE /brands/intake_block_definitions/bulk
 */
class BulkDeleteIntakeBlockDefinitionsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_block_definitions.BULK_DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/intake_block_definitions/bulk - Loescht mehrere Block-Definitionen in einem Request. Body MUSS {block_definition_ids:[int]} enthalten.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden alle Deletes in einer DB-Transaktion ausgefuehrt (bei einem Fehler wird alles zurueckgerollt). Standard: false.',
                ],
                'block_definition_ids' => [
                    'type' => 'array',
                    'description' => 'Liste von Block-Definition-IDs die geloescht werden sollen.',
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
            ],
            'required' => ['block_definition_ids'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $blockDefinitionIds = $arguments['block_definition_ids'] ?? null;
            if (!is_array($blockDefinitionIds) || empty($blockDefinitionIds)) {
                return ToolResult::error('INVALID_ARGUMENT', 'block_definition_ids muss ein nicht-leeres Array sein.');
            }

            $atomic = (bool) ($arguments['atomic'] ?? false);
            $singleTool = new DeleteIntakeBlockDefinitionTool();

            $run = function () use ($blockDefinitionIds, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($blockDefinitionIds as $idx => $id) {
                    if (!is_int($id) && !is_numeric($id)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Block-Definition-ID muss eine Ganzzahl sein.'],
                        ];
                        continue;
                    }

                    $res = $singleTool->execute(['block_definition_id' => (int) $id], $context);
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
                        'requested' => count($blockDefinitionIds),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn () => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Delete der Block-Definitionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'intake_block_definitions', 'bulk', 'batch', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'high',
            'idempotent' => false,
        ];
    }
}
