<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsIntakeSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen einer einzelnen Intake Session
 */
class GetIntakeSessionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_session.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/intake_sessions/{id} - Ruft eine einzelne Intake Session ab inkl. Board und Steps. REST-Parameter: id (required, integer) - Intake Session-ID. Nutze "brands.intake_sessions.GET" um verfuegbare Intake Session-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Intake Session. Nutze "brands.intake_sessions.GET" um verfuegbare Intake Session-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Intake Session-ID ist erforderlich. Nutze "brands.intake_sessions.GET" um Intake Sessions zu finden.');
            }

            // Session holen mit Relationen
            $session = BrandsIntakeSession::with(['intakeBoard', 'steps.blockDefinition'])
                ->find($arguments['id']);

            if (!$session) {
                return ToolResult::error('INTAKE_SESSION_NOT_FOUND', 'Die angegebene Intake Session wurde nicht gefunden. Nutze "brands.intake_sessions.GET" um alle verfuegbaren Intake Sessions zu sehen.');
            }

            // Policy pruefen ueber das Board
            $board = $session->intakeBoard;
            if (!$board) {
                return ToolResult::error('INTAKE_BOARD_NOT_FOUND', 'Das zugehoerige Intake Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Intake Session (Policy).');
            }

            $stepsData = $session->steps->map(function ($step) {
                return [
                    'id' => $step->id,
                    'uuid' => $step->uuid,
                    'board_block_id' => $step->board_block_id,
                    'block_definition_id' => $step->block_definition_id,
                    'block_definition_name' => $step->blockDefinition?->name,
                    'block_type' => $step->blockDefinition?->block_type,
                    'answers' => $step->answers,
                    'ai_interpretation' => $step->ai_interpretation,
                    'ai_confidence' => $step->ai_confidence,
                    'ai_suggestions' => $step->ai_suggestions,
                    'user_clarification_needed' => $step->user_clarification_needed,
                    'message_count' => $step->message_count,
                    'clarification_attempts' => $step->clarification_attempts,
                    'is_completed' => $step->is_completed,
                    'completed_at' => $step->completed_at?->toIso8601String(),
                ];
            })->values()->toArray();

            $data = [
                'id' => $session->id,
                'uuid' => $session->uuid,
                'session_token' => $session->session_token,
                'status' => $session->status,
                'respondent_name' => $session->respondent_name,
                'respondent_email' => $session->respondent_email,
                'current_step' => $session->current_step,
                'answers' => $session->answers,
                'metadata' => $session->metadata,
                'intake_board_id' => $session->intake_board_id,
                'intake_board_name' => $board->name,
                'started_at' => $session->started_at?->toIso8601String(),
                'completed_at' => $session->completed_at?->toIso8601String(),
                'created_at' => $session->created_at->toIso8601String(),
                'steps' => $stepsData,
                'steps_count' => count($stepsData),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Intake Session: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake_session', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
