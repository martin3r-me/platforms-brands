<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsIntakeBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Schließen eines IntakeBoards
 */
class CloseIntakeBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_boards.close';
    }

    public function getDescription(): string
    {
        return 'POST /brands/intake_boards/{id}/close - Schließt ein Intake Board (Erhebung). Setzt den Status auf "closed" und deaktiviert das Board. REST-Parameter: intake_board_id (required, integer) - Intake Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'intake_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu schließenden Intake Boards (ERFORDERLICH). Nutze "brands.intake_boards.GET" um Intake Boards zu finden.'
                ],
            ],
            'required' => ['intake_board_id']
        ];
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
                return ToolResult::error('INTAKE_BOARD_NOT_FOUND', 'Das angegebene Intake Board wurde nicht gefunden. Nutze "brands.intake_boards.GET" um Intake Boards zu finden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $intakeBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Intake Board nicht schließen (Policy).');
            }

            // Board schließen
            $intakeBoard->close();

            $intakeBoard->refresh();
            $intakeBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'intake_board_id' => $intakeBoard->id,
                'intake_board_name' => $intakeBoard->name,
                'status' => $intakeBoard->status,
                'is_active' => $intakeBoard->is_active,
                'brand_id' => $intakeBoard->brand_id,
                'brand_name' => $intakeBoard->brand->name,
                'completed_at' => $intakeBoard->completed_at?->toIso8601String(),
                'updated_at' => $intakeBoard->updated_at->toIso8601String(),
                'message' => "Intake Board '{$intakeBoard->name}' wurde erfolgreich geschlossen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Schließen des Intake Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'intake_board', 'close'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
            'side_effects' => ['updates'],
        ];
    }
}
