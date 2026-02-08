<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsKanbanBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen KanbanBoards
 */
class GetKanbanBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.kanban_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/kanban_boards/{id} - Ruft ein einzelnes Kanban Board ab. REST-Parameter: id (required, integer) - Kanban Board-ID. Nutze "brands.kanban_boards.GET" um verfügbare Kanban Board-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Kanban Boards. Nutze "brands.kanban_boards.GET" um verfügbare Kanban Board-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Kanban Board-ID ist erforderlich. Nutze "brands.kanban_boards.GET" um Kanban Boards zu finden.');
            }

            // KanbanBoard holen
            $kanbanBoard = BrandsKanbanBoard::with(['brand', 'user', 'team', 'slots', 'cards'])
                ->find($arguments['id']);

            if (!$kanbanBoard) {
                return ToolResult::error('KANBAN_BOARD_NOT_FOUND', 'Das angegebene Kanban Board wurde nicht gefunden. Nutze "brands.kanban_boards.GET" um alle verfügbaren Kanban Boards zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $kanbanBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Kanban Board (Policy).');
            }

            $data = [
                'id' => $kanbanBoard->id,
                'uuid' => $kanbanBoard->uuid,
                'name' => $kanbanBoard->name,
                'description' => $kanbanBoard->description,
                'brand_id' => $kanbanBoard->brand_id,
                'brand_name' => $kanbanBoard->brand->name,
                'team_id' => $kanbanBoard->team_id,
                'user_id' => $kanbanBoard->user_id,
                'done' => $kanbanBoard->done,
                'done_at' => $kanbanBoard->done_at?->toIso8601String(),
                'slots_count' => $kanbanBoard->slots->count(),
                'cards_count' => $kanbanBoard->cards->count(),
                'created_at' => $kanbanBoard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Kanban Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'kanban_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
