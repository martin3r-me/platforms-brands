<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateIntakeBlockDefinitionTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.intake_block_definitions.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/intake_block_definitions/{id} - Aktualisiert eine Intake Block-Definition. REST-Parameter: block_definition_id (required, integer) - Block-Definition-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. block_type (optional, string) - Block-Typ. ai_prompt (optional, string) - AI-Prompt. is_active (optional, boolean) - Aktiv-Status.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'block_definition_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Block-Definition (ERFORDERLICH).'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name der Block-Definition.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung der Block-Definition.'
                ],
                'block_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Block-Typ. Erlaubte Werte: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom.',
                    'enum' => BrandsIntakeBlockDefinition::BLOCK_TYPES
                ],
                'ai_prompt' => [
                    'type' => 'string',
                    'description' => 'Optional: AI-Prompt fuer die Block-Definition.'
                ],
                'conditional_logic' => [
                    'type' => 'object',
                    'description' => 'Optional: Bedingte Logik als JSON-Objekt.'
                ],
                'response_format' => [
                    'type' => 'object',
                    'description' => 'Optional: Antwortformat als JSON-Objekt.'
                ],
                'fallback_questions' => [
                    'type' => 'array',
                    'description' => 'Optional: Fallback-Fragen als Array.'
                ],
                'validation_rules' => [
                    'type' => 'object',
                    'description' => 'Optional: Validierungsregeln als JSON-Objekt.'
                ],
                'logic_config' => [
                    'type' => 'object',
                    'description' => 'Optional: Logik-Konfiguration als JSON-Objekt.'
                ],
                'ai_behavior' => [
                    'type' => 'object',
                    'description' => 'Optional: AI-Verhalten als JSON-Objekt.'
                ],
                'min_confidence_threshold' => [
                    'type' => 'number',
                    'description' => 'Optional: Minimaler Konfidenz-Schwellenwert (z.B. 0.80).'
                ],
                'max_clarification_attempts' => [
                    'type' => 'integer',
                    'description' => 'Optional: Maximale Anzahl an Klaerungsversuchen.'
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Aktiv-Status der Block-Definition.'
                ],
            ],
            'required' => ['block_definition_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'block_definition_id', BrandsIntakeBlockDefinition::class,
                'BLOCK_DEFINITION_NOT_FOUND', 'Die angegebene Block-Definition wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $blockDefinition = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $blockDefinition);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Block-Definition nicht bearbeiten (Policy).');
            }

            // block_type validieren falls angegeben
            if (isset($arguments['block_type']) && !in_array($arguments['block_type'], BrandsIntakeBlockDefinition::BLOCK_TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', "Ungueltiger block_type '{$arguments['block_type']}'. Erlaubte Werte: " . implode(', ', BrandsIntakeBlockDefinition::BLOCK_TYPES));
            }

            $updateData = [];
            $updatableFields = [
                'name', 'description', 'block_type', 'ai_prompt',
                'conditional_logic', 'response_format', 'fallback_questions',
                'validation_rules', 'logic_config', 'ai_behavior',
                'min_confidence_threshold', 'max_clarification_attempts', 'is_active',
            ];

            foreach ($updatableFields as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $blockDefinition->update($updateData);
            }

            $blockDefinition->refresh();
            $blockDefinition->load(['user', 'team']);

            return ToolResult::success([
                'id' => $blockDefinition->id,
                'uuid' => $blockDefinition->uuid,
                'name' => $blockDefinition->name,
                'block_type' => $blockDefinition->block_type,
                'block_type_label' => $blockDefinition->getBlockTypeLabel(),
                'description' => $blockDefinition->description,
                'ai_prompt' => $blockDefinition->ai_prompt,
                'conditional_logic' => $blockDefinition->conditional_logic,
                'response_format' => $blockDefinition->response_format,
                'fallback_questions' => $blockDefinition->fallback_questions,
                'validation_rules' => $blockDefinition->validation_rules,
                'logic_config' => $blockDefinition->logic_config,
                'ai_behavior' => $blockDefinition->ai_behavior,
                'min_confidence_threshold' => $blockDefinition->min_confidence_threshold,
                'max_clarification_attempts' => $blockDefinition->max_clarification_attempts,
                'is_active' => $blockDefinition->is_active,
                'team_id' => $blockDefinition->team_id,
                'user_id' => $blockDefinition->user_id,
                'updated_at' => $blockDefinition->updated_at->toIso8601String(),
                'message' => "Block-Definition '{$blockDefinition->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Block-Definition: ' . $e->getMessage());
        }
    }
}
