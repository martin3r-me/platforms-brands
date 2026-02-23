<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsIntakeSession;
use Platform\Brands\Models\BrandsIntakeStep;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von Intake Steps einer Intake Session
 */
class ListIntakeStepsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.intake_steps.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/intake_sessions/{session_id}/intake_steps - Listet Intake Steps einer Intake Session auf. REST-Parameter: session_id (required, integer) - Intake Session-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'session_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID der Intake Session. Nutze "brands.intake_sessions.GET" um Intake Sessions zu finden.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $sessionId = $arguments['session_id'] ?? null;
            if (!$sessionId) {
                return ToolResult::error('VALIDATION_ERROR', 'session_id ist erforderlich.');
            }

            $session = BrandsIntakeSession::with('intakeBoard')->find($sessionId);
            if (!$session) {
                return ToolResult::error('INTAKE_SESSION_NOT_FOUND', 'Die angegebene Intake Session wurde nicht gefunden.');
            }

            // Policy pruefen ueber das Board
            $board = $session->intakeBoard;
            if (!$board) {
                return ToolResult::error('INTAKE_BOARD_NOT_FOUND', 'Das zugehoerige Intake Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Intake Board.');
            }

            // Query aufbauen - Intake Steps
            $query = BrandsIntakeStep::query()
                ->where('session_id', $sessionId)
                ->with(['boardBlock.blockDefinition']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'is_completed', 'user_clarification_needed', 'ai_confidence', 'created_at', 'updated_at'
            ]);

            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, []);

            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'is_completed', 'ai_confidence', 'message_count', 'created_at', 'updated_at'
            ], 'created_at', 'asc');

            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            $steps = $query->get();

            // Steps formatieren
            $stepsList = $steps->map(function ($step) {
                return [
                    'id' => $step->id,
                    'uuid' => $step->uuid,
                    'session_id' => $step->session_id,
                    'board_block_id' => $step->board_block_id,
                    'block_definition_id' => $step->block_definition_id,
                    'block_definition_name' => $step->boardBlock?->blockDefinition?->name,
                    'block_type' => $step->boardBlock?->blockDefinition?->block_type,
                    'answers' => $step->answers,
                    'ai_interpretation' => $step->ai_interpretation,
                    'ai_confidence' => $step->ai_confidence,
                    'is_completed' => $step->is_completed,
                    'user_clarification_needed' => $step->user_clarification_needed,
                    'message_count' => $step->message_count,
                    'completed_at' => $step->completed_at?->toIso8601String(),
                    'created_at' => $step->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'intake_steps' => $stepsList,
                'count' => count($stepsList),
                'session_id' => $sessionId,
                'session_token' => $session->session_token,
                'message' => count($stepsList) > 0
                    ? count($stepsList) . ' Intake Step(s) gefunden fuer Session "' . $session->session_token . '".'
                    : 'Keine Intake Steps gefunden fuer Session "' . $session->session_token . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Intake Steps: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake_step', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
