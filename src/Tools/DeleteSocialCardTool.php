<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSocialCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen von SocialCards im Brands-Modul
 */
class DeleteSocialCardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.social_cards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/social_cards/{id} - Löscht eine Social Card. REST-Parameter: social_card_id (required, integer) - Social Card-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'social_card_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Social Card (ERFORDERLICH). Nutze "brands.social_cards.GET" um Social Cards zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass die Social Card wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['social_card_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'social_card_id',
                BrandsSocialCard::class,
                'SOCIAL_CARD_NOT_FOUND',
                'Die angegebene Social Card wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $socialCard = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $socialCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Social Card nicht löschen (Policy).');
            }

            $socialCardTitle = $socialCard->title;
            $socialCardId = $socialCard->id;
            $socialBoardId = $socialCard->social_board_id;
            $teamId = $socialCard->team_id;

            // SocialCard löschen
            $socialCard->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.social_cards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'social_card_id' => $socialCardId,
                'social_card_title' => $socialCardTitle,
                'social_board_id' => $socialBoardId,
                'message' => "Social Card '{$socialCardTitle}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Social Card: ' . $e->getMessage());
        }
    }
}
