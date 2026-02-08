<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsKanbanBoard;
use Platform\Brands\Models\BrandsKanbanBoardSlot;
use Platform\Brands\Models\BrandsKanbanCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen von KanbanCards im Brands-Modul
 */
class CreateKanbanCardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.kanban_cards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/kanban_boards/{kanban_board_id}/slots/{slot_id}/kanban_cards - Erstellt eine neue Kanban Card. REST-Parameter: kanban_board_id (required, integer) - Kanban Board-ID. kanban_board_slot_id (required, integer) - Slot-ID. title (optional, string) - Titel. description (optional, string) - Beschreibung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kanban_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Kanban Boards (ERFORDERLICH). Nutze "brands.kanban_boards.GET" um Kanban Boards zu finden.'
                ],
                'kanban_board_slot_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Slots im Kanban Board (ERFORDERLICH). Nutze "brands.kanban_board.GET" um Slots zu sehen.'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Titel der Kanban Card. Wenn nicht angegeben, wird automatisch "Neue Card" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung der Kanban Card.'
                ],
            ],
            'required' => ['kanban_board_id', 'kanban_board_slot_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // KanbanBoard finden
            $kanbanBoardId = $arguments['kanban_board_id'] ?? null;
            if (!$kanbanBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'kanban_board_id ist erforderlich.');
            }

            $kanbanBoard = BrandsKanbanBoard::find($kanbanBoardId);
            if (!$kanbanBoard) {
                return ToolResult::error('KANBAN_BOARD_NOT_FOUND', 'Das angegebene Kanban Board wurde nicht gefunden.');
            }

            // Slot finden
            $slotId = $arguments['kanban_board_slot_id'] ?? null;
            if (!$slotId) {
                return ToolResult::error('VALIDATION_ERROR', 'kanban_board_slot_id ist erforderlich.');
            }

            $slot = BrandsKanbanBoardSlot::find($slotId);
            if (!$slot || $slot->kanban_board_id != $kanbanBoardId) {
                return ToolResult::error('SLOT_NOT_FOUND', 'Der angegebene Slot wurde nicht gefunden oder gehört nicht zu diesem Kanban Board.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $kanbanBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Cards für dieses Kanban Board erstellen (Policy).');
            }

            $title = $arguments['title'] ?? 'Neue Card';

            // KanbanCard direkt erstellen
            $kanbanCard = BrandsKanbanCard::create([
                'title' => $title,
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $kanbanBoard->team_id,
                'kanban_board_id' => $kanbanBoard->id,
                'kanban_board_slot_id' => $slot->id,
            ]);

            $kanbanCard->load(['kanbanBoard', 'slot', 'user', 'team']);

            return ToolResult::success([
                'id' => $kanbanCard->id,
                'uuid' => $kanbanCard->uuid,
                'title' => $kanbanCard->title,
                'description' => $kanbanCard->description,
                'kanban_board_id' => $kanbanCard->kanban_board_id,
                'kanban_board_name' => $kanbanCard->kanbanBoard->name,
                'slot_id' => $kanbanCard->kanban_board_slot_id,
                'slot_name' => $kanbanCard->slot->name,
                'team_id' => $kanbanCard->team_id,
                'created_at' => $kanbanCard->created_at->toIso8601String(),
                'message' => "Kanban Card '{$kanbanCard->title}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Kanban Card: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'kanban_card', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
