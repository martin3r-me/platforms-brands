<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateIntakeBlockDefinitionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_block_definitions.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/intake_block_definitions - Erstellt eine neue Block-Definition (team-weit, nicht brand-spezifisch). REST-Parameter: name (required, string) - Name der Block-Definition. block_type (required, string) - Typ des Blocks (text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom). description (optional, string) - Beschreibung. ai_prompt (optional, string) - AI-Prompt. team_id (optional, integer) - Team-ID, Standard: aktuelles Team.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Block-Definition (ERFORDERLICH).'
                ],
                'block_type' => [
                    'type' => 'string',
                    'description' => 'Typ des Blocks (ERFORDERLICH). Erlaubte Werte: text, long_text, email, phone, url, select, multi_select, number, scale, date, boolean, file, rating, location, info, custom.',
                    'enum' => BrandsIntakeBlockDefinition::BLOCK_TYPES
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung der Block-Definition.'
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
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (optional): Team-ID. Wenn nicht angegeben, wird automatisch das aktuelle Team aus dem Kontext verwendet.'
                ],
            ],
            'required' => ['name', 'block_type']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $name = $arguments['name'] ?? null;
            if (!$name) {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $blockType = $arguments['block_type'] ?? null;
            if (!$blockType) {
                return ToolResult::error('VALIDATION_ERROR', 'block_type ist erforderlich.');
            }

            if (!in_array($blockType, BrandsIntakeBlockDefinition::BLOCK_TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', "Ungueltiger block_type '{$blockType}'. Erlaubte Werte: " . implode(', ', BrandsIntakeBlockDefinition::BLOCK_TYPES));
            }

            // Team-ID bestimmen
            $teamId = $arguments['team_id'] ?? $context->team?->id;
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden.');
            }

            // Pruefen, ob User Zugriff auf dieses Team hat
            $userHasAccess = $context->user->teams()->where('teams.id', $teamId)->exists();
            if (!$userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamId}.");
            }

            try {
                Gate::forUser($context->user)->authorize('create', BrandsIntakeBlockDefinition::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Block-Definitionen erstellen (Policy).');
            }

            $blockDefinition = BrandsIntakeBlockDefinition::create([
                'name' => $name,
                'block_type' => $blockType,
                'description' => $arguments['description'] ?? null,
                'ai_prompt' => $arguments['ai_prompt'] ?? null,
                'conditional_logic' => $arguments['conditional_logic'] ?? null,
                'response_format' => $arguments['response_format'] ?? null,
                'fallback_questions' => $arguments['fallback_questions'] ?? null,
                'validation_rules' => $arguments['validation_rules'] ?? null,
                'logic_config' => $arguments['logic_config'] ?? null,
                'ai_behavior' => $arguments['ai_behavior'] ?? null,
                'min_confidence_threshold' => $arguments['min_confidence_threshold'] ?? null,
                'max_clarification_attempts' => $arguments['max_clarification_attempts'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $teamId,
            ]);

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
                'created_at' => $blockDefinition->created_at->toIso8601String(),
                'message' => "Block-Definition '{$blockDefinition->name}' ({$blockDefinition->getBlockTypeLabel()}) erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Block-Definition: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'intake_block_definition', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
