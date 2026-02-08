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
 * Tool zum Bearbeiten von KanbanBoards
 */
class UpdateKanbanBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.kanban_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/kanban_boards/{id} - Aktualisiert ein Kanban Board. REST-Parameter: kanban_board_id (required, integer) - Kanban Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. done (optional, boolean) - Als erledigt markieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kanban_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des KanbanBoards (ERFORDERLICH). Nutze "brands.kanban_boards.GET" um Kanban Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des Kanban Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Kanban Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Kanban Board als erledigt markieren.'
                ],
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
                Gate::forUser($context->user)->authorize('update', $kanbanBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Kanban Board nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                if ($arguments['done']) {
                    $updateData['done_at'] = now();
                } else {
                    $updateData['done_at'] = null;
                }
            }

            // KanbanBoard aktualisieren
            if (!empty($updateData)) {
                $kanbanBoard->update($updateData);
            }

            $kanbanBoard->refresh();
            $kanbanBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'kanban_board_id' => $kanbanBoard->id,
                'kanban_board_name' => $kanbanBoard->name,
                'description' => $kanbanBoard->description,
                'brand_id' => $kanbanBoard->brand_id,
                'brand_name' => $kanbanBoard->brand->name,
                'team_id' => $kanbanBoard->team_id,
                'done' => $kanbanBoard->done,
                'done_at' => $kanbanBoard->done_at?->toIso8601String(),
                'updated_at' => $kanbanBoard->updated_at->toIso8601String(),
                'message' => "Kanban Board '{$kanbanBoard->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Kanban Boards: ' . $e->getMessage());
        }
    }
}
