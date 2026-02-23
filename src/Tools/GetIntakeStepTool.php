<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsIntakeStep;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen Intake Steps
 */
class GetIntakeStepTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_step.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/intake_steps/{id} - Ruft einen einzelnen Intake Step ab inkl. Session und Block-Definition. REST-Parameter: id (required, integer) - Intake Step-ID. Nutze "brands.intake_steps.GET" um verfuegbare Intake Step-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Intake Steps. Nutze "brands.intake_steps.GET" um verfuegbare Intake Step-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Intake Step-ID ist erforderlich. Nutze "brands.intake_steps.GET" um Intake Steps zu finden.');
            }

            // Step holen mit Relationen
            $step = BrandsIntakeStep::with(['session', 'boardBlock.blockDefinition'])
                ->find($arguments['id']);

            if (!$step) {
                return ToolResult::error('INTAKE_STEP_NOT_FOUND', 'Der angegebene Intake Step wurde nicht gefunden. Nutze "brands.intake_steps.GET" um alle verfuegbaren Intake Steps zu sehen.');
            }

            // Policy pruefen ueber das Board
            $board = $step->boardBlock?->intakeBoard;
            if (!$board) {
                // Lade Board ueber Session als Fallback
                $board = $step->session?->intakeBoard;
            }

            if (!$board) {
                return ToolResult::error('INTAKE_BOARD_NOT_FOUND', 'Das zugehoerige Intake Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Intake Step (Policy).');
            }

            $data = [
                'id' => $step->id,
                'uuid' => $step->uuid,
                'session_id' => $step->session_id,
                'session_token' => $step->session?->session_token,
                'board_block_id' => $step->board_block_id,
                'block_definition_id' => $step->block_definition_id,
                'block_definition_name' => $step->boardBlock?->blockDefinition?->name,
                'block_type' => $step->boardBlock?->blockDefinition?->block_type,
                'block_type_label' => $step->boardBlock?->blockDefinition?->getBlockTypeLabel(),
                'answers' => $step->answers,
                'ai_interpretation' => $step->ai_interpretation,
                'ai_confidence' => $step->ai_confidence,
                'ai_suggestions' => $step->ai_suggestions,
                'user_clarification_needed' => $step->user_clarification_needed,
                'conversation_context' => $step->conversation_context,
                'message_count' => $step->message_count,
                'clarification_attempts' => $step->clarification_attempts,
                'is_completed' => $step->is_completed,
                'completed_at' => $step->completed_at?->toIso8601String(),
                'created_at' => $step->created_at->toIso8601String(),
                'updated_at' => $step->updated_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Intake Steps: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake_step', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
