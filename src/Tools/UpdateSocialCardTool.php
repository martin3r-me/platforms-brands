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
 * Tool zum Bearbeiten von SocialCards
 */
class UpdateSocialCardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.social_cards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/social_cards/{id} - Aktualisiert eine Social Card. REST-Parameter: social_card_id (required, integer) - Social Card-ID. title (optional, string) - Titel. body_md (optional, string) - Markdown-Inhalt (Caption/Text). description (optional, string) - Beschreibung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'social_card_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Social Card (ERFORDERLICH). Nutze "brands.social_cards.GET" um Social Cards zu finden.'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Titel der Social Card.'
                ],
                'body_md' => [
                    'type' => 'string',
                    'description' => 'Optional: Markdown-Inhalt der Social Card (Caption/Text).'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung der Social Card.'
                ],
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
                Gate::forUser($context->user)->authorize('update', $socialCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Social Card nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['title'])) {
                $updateData['title'] = $arguments['title'];
            }

            if (isset($arguments['body_md'])) {
                $updateData['body_md'] = $arguments['body_md'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            // SocialCard aktualisieren
            if (!empty($updateData)) {
                $socialCard->update($updateData);
            }

            $socialCard->refresh();
            $socialCard->load(['socialBoard', 'slot', 'user', 'team']);

            return ToolResult::success([
                'social_card_id' => $socialCard->id,
                'title' => $socialCard->title,
                'body_md' => $socialCard->body_md,
                'description' => $socialCard->description,
                'social_board_id' => $socialCard->social_board_id,
                'social_board_name' => $socialCard->socialBoard->name,
                'updated_at' => $socialCard->updated_at->toIso8601String(),
                'message' => "Social Card '{$socialCard->title}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Social Card: ' . $e->getMessage());
        }
    }
}
