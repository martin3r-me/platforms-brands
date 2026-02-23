<?php

namespace Platform\Brands\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;

/**
 * Bulk Create: mehrere Block-Definitionen in einem Call anlegen.
 *
 * Sinn: reduziert Toolcalls/Iterationen (LLM kann 10+ Block-Definitionen in einem Schritt erstellen).
 * REST-Idee: POST /brands/intake_block_definitions/bulk
 */
class BulkCreateIntakeBlockDefinitionsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_block_definitions.BULK_POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/intake_block_definitions/bulk - Erstellt mehrere Block-Definitionen in einem Call. Body MUSS {block_definitions:[{name, block_type, ...}], defaults?} enthalten.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, alle Creates in einer DB-Transaktion. Standard: false.',
                ],
                'defaults' => [
                    'type' => 'object',
                    'description' => 'Optional: Default-Werte fuer alle Block-Definitionen.',
                    'properties' => [
                        'block_type' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'ai_prompt' => ['type' => 'string'],
                        'min_confidence_threshold' => ['type' => 'number'],
                        'max_clarification_attempts' => ['type' => 'integer'],
                        'team_id' => ['type' => 'integer'],
                    ],
                ],
                'block_definitions' => [
                    'type' => 'array',
                    'description' => 'Liste von Block-Definitionen. Jedes Element: {name, block_type, ...}.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
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
                            'team_id' => ['type' => 'integer'],
                        ],
                        'required' => ['name'],
                    ],
                ],
            ],
            'required' => ['block_definitions'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $blockDefinitions = $arguments['block_definitions'] ?? null;
            if (!is_array($blockDefinitions) || empty($blockDefinitions)) {
                return ToolResult::error('INVALID_ARGUMENT', 'block_definitions muss ein nicht-leeres Array sein.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool) ($arguments['atomic'] ?? false);
            $singleTool = new CreateIntakeBlockDefinitionTool();

            $run = function () use ($blockDefinitions, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($blockDefinitions as $idx => $bd) {
                    if (!is_array($bd)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Block-Definition-Item muss ein Objekt sein.'],
                        ];
                        continue;
                    }

                    // Defaults anwenden, ohne explizite Werte zu ueberschreiben
                    $payload = $defaults;
                    foreach ($bd as $k => $v) {
                        $payload[$k] = $v;
                    }

                    $res = $singleTool->execute($payload, $context);
                    if ($res->success) {
                        $okCount++;
                        $results[] = ['index' => $idx, 'ok' => true, 'data' => $res->data];
                    } else {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => $res->errorCode, 'message' => $res->error],
                        ];
                    }
                }

                return [
                    'results' => $results,
                    'summary' => [
                        'requested' => count($blockDefinitions),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn () => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Create der Block-Definitionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['brands', 'intake_block_definitions', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
