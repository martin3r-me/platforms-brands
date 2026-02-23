<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsIntakeBoard;
use Platform\Brands\Models\BrandsIntakeSession;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von Intake Sessions eines Intake Boards
 */
class ListIntakeSessionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.intake_sessions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/intake_boards/{intake_board_id}/intake_sessions - Listet Intake Sessions eines Intake Boards auf. REST-Parameter: intake_board_id (required, integer) - Intake Board-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'intake_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Intake Boards. Nutze "brands.intake_boards.GET" um Intake Boards zu finden.'
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

            $intakeBoardId = $arguments['intake_board_id'] ?? null;
            if (!$intakeBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'intake_board_id ist erforderlich.');
            }

            $intakeBoard = BrandsIntakeBoard::find($intakeBoardId);
            if (!$intakeBoard) {
                return ToolResult::error('INTAKE_BOARD_NOT_FOUND', 'Das angegebene Intake Board wurde nicht gefunden.');
            }

            // Policy pruefen
            if (!Gate::forUser($context->user)->allows('view', $intakeBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Intake Board.');
            }

            // Query aufbauen - Intake Sessions
            $query = BrandsIntakeSession::query()
                ->where('intake_board_id', $intakeBoardId);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'status', 'current_step', 'started_at', 'completed_at', 'created_at', 'updated_at'
            ]);

            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['session_token', 'status']);

            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'status', 'current_step', 'started_at', 'completed_at', 'created_at', 'updated_at'
            ], 'created_at', 'desc');

            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            $sessions = $query->get();

            // Sessions formatieren
            $sessionsList = $sessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'uuid' => $session->uuid,
                    'session_token' => $session->session_token,
                    'status' => $session->status,
                    'respondent_name' => $session->respondent_name,
                    'current_step' => $session->current_step,
                    'started_at' => $session->started_at?->toIso8601String(),
                    'completed_at' => $session->completed_at?->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'intake_sessions' => $sessionsList,
                'count' => count($sessionsList),
                'intake_board_id' => $intakeBoardId,
                'intake_board_name' => $intakeBoard->name,
                'message' => count($sessionsList) > 0
                    ? count($sessionsList) . ' Intake Session(s) gefunden fuer Intake Board "' . $intakeBoard->name . '".'
                    : 'Keine Intake Sessions gefunden fuer Intake Board "' . $intakeBoard->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Intake Sessions: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake_session', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
