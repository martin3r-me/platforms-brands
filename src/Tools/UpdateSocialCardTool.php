<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSocialCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Carbon\Carbon;

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
        return 'PUT /brands/social_cards/{id} - Aktualisiert eine Social Card. REST-Parameter: social_card_id (required, integer) - Social Card-ID. title (optional, string) - Titel. body_md (optional, string) - Markdown-Inhalt (Caption/Text). description (optional, string) - Beschreibung. publish_at (optional, string) - Geplanter Veröffentlichungszeitpunkt (ISO8601). status (optional, string) - Status: draft|scheduled|publishing|published|failed.';
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
                'publish_at' => [
                    'type' => 'string',
                    'description' => 'Optional: Geplanter Veröffentlichungszeitpunkt im ISO8601-Format (z.B. "2025-03-01T10:00:00Z"). Setzt automatisch status auf "scheduled".'
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'scheduled'],
                    'description' => 'Optional: Status der Social Card. Nur "draft" und "scheduled" können manuell gesetzt werden.'
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

            if (isset($arguments['publish_at'])) {
                try {
                    $updateData['publish_at'] = Carbon::parse($arguments['publish_at']);
                    // Automatisch auf scheduled setzen wenn publish_at gesetzt wird
                    if (!isset($arguments['status'])) {
                        $updateData['status'] = BrandsSocialCard::STATUS_SCHEDULED;
                    }
                } catch (\Throwable $e) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültiges Datum für publish_at. Bitte ISO8601-Format verwenden (z.B. "2025-03-01T10:00:00Z").');
                }
            }

            if (isset($arguments['status'])) {
                if (!in_array($arguments['status'], ['draft', 'scheduled'])) {
                    return ToolResult::error('VALIDATION_ERROR', 'Status kann nur auf "draft" oder "scheduled" gesetzt werden. Andere Status werden vom System gesetzt.');
                }
                $updateData['status'] = $arguments['status'];
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
                'status' => $socialCard->status,
                'publish_at' => $socialCard->publish_at?->toIso8601String(),
                'published_at' => $socialCard->published_at?->toIso8601String(),
                'updated_at' => $socialCard->updated_at->toIso8601String(),
                'message' => "Social Card '{$socialCard->title}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Social Card: ' . $e->getMessage());
        }
    }
}
