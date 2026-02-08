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
 * Tool zum Löschen von KanbanCards im Brands-Modul
 */
class DeleteKanbanCardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.kanban_cards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/kanban_cards/{id} - Löscht eine Kanban Card. REST-Parameter: kanban_card_id (required, integer) - Kanban Card-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kanban_card_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Kanban Card (ERFORDERLICH). Nutze "brands.kanban_cards.GET" um Kanban Cards zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass die Kanban Card wirklich gelöscht werden soll.'
                ]
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
                Gate::forUser($context->user)->authorize('delete', $kanbanCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Kanban Card nicht löschen (Policy).');
            }

            $kanbanCardTitle = $kanbanCard->title;
            $kanbanCardId = $kanbanCard->id;
            $kanbanBoardId = $kanbanCard->kanban_board_id;
            $teamId = $kanbanCard->team_id;

            // KanbanCard löschen
            $kanbanCard->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.kanban_cards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'kanban_card_id' => $kanbanCardId,
                'kanban_card_title' => $kanbanCardTitle,
                'kanban_board_id' => $kanbanBoardId,
                'message' => "Kanban Card '{$kanbanCardTitle}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Kanban Card: ' . $e->getMessage());
        }
    }
}
