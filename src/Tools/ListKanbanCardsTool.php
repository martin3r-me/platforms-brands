<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsKanbanBoard;
use Platform\Brands\Models\BrandsKanbanCard;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von KanbanCards im Brands-Modul
 */
class ListKanbanCardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.kanban_cards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/kanban_boards/{kanban_board_id}/kanban_cards - Listet Kanban Cards eines Kanban Boards auf. REST-Parameter: kanban_board_id (required, integer) - Kanban Board-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'kanban_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Kanban Boards. Nutze "brands.kanban_boards.GET" um Kanban Boards zu finden.'
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

            $kanbanBoardId = $arguments['kanban_board_id'] ?? null;
            if (!$kanbanBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'kanban_board_id ist erforderlich.');
            }

            $kanbanBoard = BrandsKanbanBoard::find($kanbanBoardId);
            if (!$kanbanBoard) {
                return ToolResult::error('KANBAN_BOARD_NOT_FOUND', 'Das angegebene Kanban Board wurde nicht gefunden.');
            }

            // Policy prüfen
            if (!Gate::forUser($context->user)->allows('view', $kanbanBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Kanban Board.');
            }

            // Query aufbauen - Kanban Cards
            $query = BrandsKanbanCard::query()
                ->where('kanban_board_id', $kanbanBoardId)
                ->with(['kanbanBoard', 'slot', 'user', 'team']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'title', 'description', 'created_at', 'updated_at'
            ]);

            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);

            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'title', 'created_at', 'updated_at', 'order'
            ], 'order', 'asc');

            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            // Cards holen und per Policy filtern
            $cards = $query->get()->filter(function ($card) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $card);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            // Cards formatieren
            $cardsList = $cards->map(function($kanbanCard) {
                return [
                    'id' => $kanbanCard->id,
                    'uuid' => $kanbanCard->uuid,
                    'title' => $kanbanCard->title,
                    'description' => $kanbanCard->description,
                    'kanban_board_id' => $kanbanCard->kanban_board_id,
                    'kanban_board_name' => $kanbanCard->kanbanBoard->name,
                    'slot_id' => $kanbanCard->kanban_board_slot_id,
                    'slot_name' => $kanbanCard->slot->name ?? null,
                    'team_id' => $kanbanCard->team_id,
                    'user_id' => $kanbanCard->user_id,
                    'created_at' => $kanbanCard->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'kanban_cards' => $cardsList,
                'count' => count($cardsList),
                'kanban_board_id' => $kanbanBoardId,
                'kanban_board_name' => $kanbanBoard->name,
                'message' => count($cardsList) > 0
                    ? count($cardsList) . ' Kanban Card(s) gefunden für Kanban Board "' . $kanbanBoard->name . '".'
                    : 'Keine Kanban Cards gefunden für Kanban Board "' . $kanbanBoard->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Kanban Cards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'kanban_card', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
