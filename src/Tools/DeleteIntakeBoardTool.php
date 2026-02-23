<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsIntakeBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen von IntakeBoards im Brands-Modul
 */
class DeleteIntakeBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.intake_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/intake_boards/{id} - Löscht ein Intake Board (Erhebung). REST-Parameter: intake_board_id (required, integer) - Intake Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'intake_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Intake Boards (ERFORDERLICH). Nutze "brands.intake_boards.GET" um Intake Boards zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass das Intake Board wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['intake_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'intake_board_id',
                BrandsIntakeBoard::class,
                'INTAKE_BOARD_NOT_FOUND',
                'Das angegebene Intake Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $intakeBoard = $validation['model'];

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $intakeBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Intake Board nicht löschen (Policy).');
            }

            $intakeBoardName = $intakeBoard->name;
            $intakeBoardId = $intakeBoard->id;
            $brandId = $intakeBoard->brand_id;
            $teamId = $intakeBoard->team_id;

            // IntakeBoard löschen
            $intakeBoard->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.intake_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'intake_board_id' => $intakeBoardId,
                'intake_board_name' => $intakeBoardName,
                'brand_id' => $brandId,
                'message' => "Intake Board '{$intakeBoardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Intake Boards: ' . $e->getMessage());
        }
    }
}
