<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetIntakeBlockDefinitionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_block_definition.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/intake_block_definitions/{id} - Ruft eine einzelne Intake Block-Definition mit allen Details ab. REST-Parameter: id (required, integer) - Block-Definition-ID. Nutze "brands.intake_block_definitions.GET" um verfuegbare Block-Definition-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Block-Definition. Nutze "brands.intake_block_definitions.GET" um verfuegbare Block-Definition-IDs zu sehen.'
                ]
            ],
            'required' => ['id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Block-Definition-ID ist erforderlich. Nutze "brands.intake_block_definitions.GET" um Block-Definitionen zu finden.');
            }

            $blockDefinition = BrandsIntakeBlockDefinition::with(['user', 'team'])
                ->find($arguments['id']);

            if (!$blockDefinition) {
                return ToolResult::error('BLOCK_DEFINITION_NOT_FOUND', 'Die angegebene Block-Definition wurde nicht gefunden. Nutze "brands.intake_block_definitions.GET" um alle verfuegbaren Block-Definitionen zu sehen.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $blockDefinition);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Block-Definition (Policy).');
            }

            $data = [
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
                'updated_at' => $blockDefinition->updated_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Block-Definition: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake_block_definition', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
