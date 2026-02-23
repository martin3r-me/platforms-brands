<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;

/**
 * Bulk Update: mehrere Block-Definitionen in einem Call aktualisieren.
 *
 * Sinn: reduziert Toolcalls/Iterationen (LLM kann 10+ Updates in einem Schritt erledigen).
 * REST-Idee: PUT /brands/intake_block_definitions/bulk
 */
class BulkUpdateIntakeBlockDefinitionsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_block_definitions.BULK_PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/intake_block_definitions/bulk - Aktualisiert mehrere Block-Definitionen in einem Request. Body MUSS {updates:[{block_definition_id, name?, block_type?, ...}]} enthalten.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden alle Updates in einer DB-Transaktion ausgefuehrt (bei einem Fehler wird alles zurueckgerollt). Standard: false.',
                ],
                'updates' => [
                    'type' => 'array',
                    'description' => 'Liste von Updates. Jedes Element entspricht den Parametern von brands.intake_block_definitions.PUT.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'block_definition_id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'block_type' => [
                                'type' => 'string',
                                'enum' => BrandsIntakeBlockDefinition::BLOCK_TYPES
                            ],
                            'description' => ['type' => 'string'],
                            'ai_prompt' => ['type' => 'string'],
                            'conditional_logic' => ['type' => 'object'],
                            'response_format' => ['type' => 'object'],
                            'fallback_questions' => ['type' => 'array'],
                            'validation_rules' => ['type' => 'object'],
                            'logic_config' => ['type' => 'object'],
                            'ai_behavior' => ['type' => 'object'],
                            'min_confidence_threshold' => ['type' => 'number'],
                            'max_clarification_attempts' => ['type' => 'integer'],
                        ],
                        'required' => ['block_definition_id'],
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
            $singleTool = new UpdateIntakeBlockDefinitionTool();

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
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Update der Block-Definitionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'intake_block_definitions', 'bulk', 'batch', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
