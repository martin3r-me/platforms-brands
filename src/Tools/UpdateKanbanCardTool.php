<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsKanbanCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Bearbeiten von KanbanCards
 */
class UpdateKanbanCardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.kanban_cards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/kanban_cards/{id} - Aktualisiert eine Kanban Card. REST-Parameter: kanban_card_id (required, integer) - Kanban Card-ID. title (optional, string) - Titel. description (optional, string) - Beschreibung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kanban_card_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Kanban Card (ERFORDERLICH). Nutze "brands.kanban_cards.GET" um Kanban Cards zu finden.'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Titel der Kanban Card.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung der Kanban Card.'
                ],
            ],
            'required' => ['kanban_card_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'kanban_card_id',
                BrandsKanbanCard::class,
                'KANBAN_CARD_NOT_FOUND',
                'Die angegebene Kanban Card wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $kanbanCard = $validation['model'];

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $kanbanCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Kanban Card nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['title'])) {
                $updateData['title'] = $arguments['title'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            // KanbanCard aktualisieren
            if (!empty($updateData)) {
                $kanbanCard->update($updateData);
            }

            $kanbanCard->refresh();
            $kanbanCard->load(['kanbanBoard', 'slot', 'user', 'team']);

            return ToolResult::success([
                'kanban_card_id' => $kanbanCard->id,
                'title' => $kanbanCard->title,
                'description' => $kanbanCard->description,
                'kanban_board_id' => $kanbanCard->kanban_board_id,
                'kanban_board_name' => $kanbanCard->kanbanBoard->name,
                'updated_at' => $kanbanCard->updated_at->toIso8601String(),
                'message' => "Kanban Card '{$kanbanCard->title}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Kanban Card: ' . $e->getMessage());
        }
    }
}
