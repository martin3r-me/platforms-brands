<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen einer einzelnen SocialCard
 */
class GetSocialCardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_card.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/social_cards/{id} - Ruft eine einzelne Social Card ab. REST-Parameter: id (required, integer) - Social Card-ID. Nutze "brands.social_cards.GET" um verfügbare Social Card-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Social Card. Nutze "brands.social_cards.GET" um verfügbare Social Card-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Social Card-ID ist erforderlich. Nutze "brands.social_cards.GET" um Social Cards zu finden.');
            }

            // SocialCard holen
            $socialCard = BrandsSocialCard::with(['socialBoard', 'slot', 'user', 'team'])
                ->find($arguments['id']);

            if (!$socialCard) {
                return ToolResult::error('SOCIAL_CARD_NOT_FOUND', 'Die angegebene Social Card wurde nicht gefunden. Nutze "brands.social_cards.GET" um alle verfügbaren Social Cards zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $socialCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Social Card (Policy).');
            }

            $data = [
                'id' => $socialCard->id,
                'uuid' => $socialCard->uuid,
                'title' => $socialCard->title,
                'body_md' => $socialCard->body_md,
                'description' => $socialCard->description,
                'social_board_id' => $socialCard->social_board_id,
                'social_board_name' => $socialCard->socialBoard->name,
                'slot_id' => $socialCard->social_board_slot_id,
                'slot_name' => $socialCard->slot->name ?? null,
                'status' => $socialCard->status ?? 'draft',
                'publish_at' => $socialCard->publish_at?->toIso8601String(),
                'published_at' => $socialCard->published_at?->toIso8601String(),
                'team_id' => $socialCard->team_id,
                'user_id' => $socialCard->user_id,
                'created_at' => $socialCard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Social Card: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'social_card', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
