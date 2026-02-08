<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsKanbanCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen einer einzelnen KanbanCard
 */
class GetKanbanCardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.kanban_card.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/kanban_cards/{id} - Ruft eine einzelne Kanban Card ab. REST-Parameter: id (required, integer) - Kanban Card-ID. Nutze "brands.kanban_cards.GET" um verfügbare Kanban Card-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Kanban Card. Nutze "brands.kanban_cards.GET" um verfügbare Kanban Card-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Kanban Card-ID ist erforderlich. Nutze "brands.kanban_cards.GET" um Kanban Cards zu finden.');
            }

            // KanbanCard holen
            $kanbanCard = BrandsKanbanCard::with(['kanbanBoard', 'slot', 'user', 'team'])
                ->find($arguments['id']);

            if (!$kanbanCard) {
                return ToolResult::error('KANBAN_CARD_NOT_FOUND', 'Die angegebene Kanban Card wurde nicht gefunden. Nutze "brands.kanban_cards.GET" um alle verfügbaren Kanban Cards zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $kanbanCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Kanban Card (Policy).');
            }

            $data = [
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

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Kanban Card: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'kanban_card', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
