<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsKanbanBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen von KanbanBoards im Brands-Modul
 */
class DeleteKanbanBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.kanban_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/kanban_boards/{id} - Löscht ein Kanban Board. REST-Parameter: kanban_board_id (required, integer) - Kanban Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kanban_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Kanban Boards (ERFORDERLICH). Nutze "brands.kanban_boards.GET" um Kanban Boards zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass das Kanban Board wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['kanban_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'kanban_board_id',
                BrandsKanbanBoard::class,
                'KANBAN_BOARD_NOT_FOUND',
                'Das angegebene Kanban Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $kanbanBoard = $validation['model'];

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $kanbanBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Kanban Board nicht löschen (Policy).');
            }

            $kanbanBoardName = $kanbanBoard->name;
            $kanbanBoardId = $kanbanBoard->id;
            $brandId = $kanbanBoard->brand_id;
            $teamId = $kanbanBoard->team_id;

            // KanbanBoard löschen
            $kanbanBoard->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.kanban_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'kanban_board_id' => $kanbanBoardId,
                'kanban_board_name' => $kanbanBoardName,
                'brand_id' => $brandId,
                'message' => "Kanban Board '{$kanbanBoardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Kanban Boards: ' . $e->getMessage());
        }
    }
}
