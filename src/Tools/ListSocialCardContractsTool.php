<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialCard;
use Platform\Brands\Models\BrandsSocialCardContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen der Contracts einer Social Card
 */
class ListSocialCardContractsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_card_contracts.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/social_cards/{id}/contracts - Listet alle Contracts einer Social Card auf. '
            . 'Jeder Contract enthält den generierten Output für ein Platform-Format. '
            . 'REST-Parameter: social_card_id (required, integer) - Social Card-ID. '
            . 'status (optional, string) - Filter nach Status: draft|ready|published|failed.';
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
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'ready', 'published', 'failed'],
                    'description' => 'Optional: Filter nach Contract-Status.'
                ],
            ],
            'required' => ['social_card_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $socialCardId = $arguments['social_card_id'] ?? null;
            if (!$socialCardId) {
                return ToolResult::error('VALIDATION_ERROR', 'social_card_id ist erforderlich.');
            }

            $socialCard = BrandsSocialCard::find($socialCardId);
            if (!$socialCard) {
                return ToolResult::error('SOCIAL_CARD_NOT_FOUND', 'Die angegebene Social Card wurde nicht gefunden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $socialCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Social Card (Policy).');
            }

            $query = BrandsSocialCardContract::where('social_card_id', $socialCard->id)
                ->with(['platformFormat.platform']);

            if (isset($arguments['status'])) {
                $query->where('status', $arguments['status']);
            }

            $contracts = $query->get();

            $contractsList = $contracts->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'uuid' => $contract->uuid,
                    'social_card_id' => $contract->social_card_id,
                    'platform_format_id' => $contract->platform_format_id,
                    'platform_name' => $contract->platformFormat->platform->name ?? null,
                    'format_name' => $contract->platformFormat->name ?? null,
                    'format_key' => $contract->platformFormat->key ?? null,
                    'contract' => $contract->contract,
                    'status' => $contract->status,
                    'published_at' => $contract->published_at?->toIso8601String(),
                    'external_post_id' => $contract->external_post_id,
                    'error_message' => $contract->error_message,
                    'created_at' => $contract->created_at->toIso8601String(),
                    'updated_at' => $contract->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'contracts' => $contractsList,
                'count' => count($contractsList),
                'social_card_id' => $socialCard->id,
                'social_card_title' => $socialCard->title,
                'message' => count($contractsList) > 0
                    ? count($contractsList) . ' Contract(s) gefunden für Social Card "' . $socialCard->title . '".'
                    : 'Keine Contracts gefunden für Social Card "' . $socialCard->title . '".',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Contracts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'social_card', 'contract', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
