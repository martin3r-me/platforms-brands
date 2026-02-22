<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialCard;
use Platform\Brands\Models\BrandsSocialCardContract;
use Platform\Brands\Services\MetaPublishingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Carbon\Carbon;

/**
 * Tool zum Publishen aller ready Contracts einer Social Card.
 *
 * ## Workflow
 *
 * 1. Alle Contracts mit status=ready laden
 * 2. Pro Contract: Platform erkennen (facebook, instagram) und via Meta API publishen
 * 3. Erfolg: published_at + external_post_id zurückschreiben, status → published
 * 4. Fehler: error_message setzen, status → failed
 * 5. Social Card Status aktualisieren (published wenn alle erfolgreich, failed wenn einer fehlgeschlagen)
 *
 * ## Fehlerbehandlung
 *
 * - Jeder Contract wird einzeln gepublished — ein Fehler stoppt nicht die anderen
 * - Bei API-Fehler: Contract erhält status=failed + error_message
 * - Social Card erhält status=failed nur wenn MINDESTENS ein Contract failed ist
 * - Social Card erhält status=published nur wenn ALLE Contracts published sind
 */
class PublishSocialCardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_cards.PUBLISH';
    }

    public function getDescription(): string
    {
        return 'POST /brands/social_cards/{id}/publish - Published alle ready Contracts einer Social Card via Meta API. '
            . 'Jeder Contract wird einzeln gepublished (Facebook und/oder Instagram). '
            . 'Bei Erfolg: published_at + external_post_id werden zurückgeschrieben. '
            . 'Bei Fehler: error_message + status=failed pro Contract. '
            . 'REST-Parameter: social_card_id (required, integer) - Social Card-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'social_card_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Social Card (ERFORDERLICH). Alle Contracts mit status=ready werden gepublished.'
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

            $socialCard = BrandsSocialCard::with(['contracts.platformFormat.platform'])->find($socialCardId);
            if (!$socialCard) {
                return ToolResult::error('SOCIAL_CARD_NOT_FOUND', 'Die angegebene Social Card wurde nicht gefunden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $socialCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Social Card nicht publishen (Policy).');
            }

            // Nur ready Contracts publishen
            $readyContracts = $socialCard->contracts->where('status', BrandsSocialCardContract::STATUS_READY);
            if ($readyContracts->isEmpty()) {
                return ToolResult::error('NO_READY_CONTRACTS', 'Keine Contracts mit Status "ready" gefunden. Generiere zuerst Contracts via "brands.social_card_contracts.POST".');
            }

            // Social Card auf publishing setzen
            $socialCard->update(['status' => BrandsSocialCard::STATUS_PUBLISHING]);

            /** @var MetaPublishingService $publishingService */
            $publishingService = resolve(MetaPublishingService::class);

            $results = [];
            $publishedCount = 0;
            $failedCount = 0;

            foreach ($readyContracts as $contract) {
                $platformKey = $contract->platformFormat->platform->key ?? null;

                $publishResult = match ($platformKey) {
                    'facebook' => $publishingService->publishToFacebook($contract, $context->user, $socialCard->team_id),
                    'instagram' => $publishingService->publishToInstagram($contract, $context->user, $socialCard->team_id),
                    default => [
                        'success' => false,
                        'external_post_id' => null,
                        'error' => "Publishing für Plattform '{$platformKey}' wird noch nicht unterstützt.",
                    ],
                };

                if ($publishResult['success']) {
                    $contract->update([
                        'status' => BrandsSocialCardContract::STATUS_PUBLISHED,
                        'published_at' => Carbon::now(),
                        'external_post_id' => $publishResult['external_post_id'],
                        'error_message' => null,
                    ]);
                    $publishedCount++;
                } else {
                    $contract->update([
                        'status' => BrandsSocialCardContract::STATUS_FAILED,
                        'error_message' => $publishResult['error'],
                    ]);
                    $failedCount++;
                }

                $results[] = [
                    'contract_id' => $contract->id,
                    'platform' => $platformKey,
                    'format' => $contract->platformFormat->name ?? null,
                    'format_key' => $contract->platformFormat->key ?? null,
                    'success' => $publishResult['success'],
                    'external_post_id' => $publishResult['external_post_id'] ?? null,
                    'error' => $publishResult['error'] ?? null,
                ];
            }

            // Social Card Status aktualisieren
            $now = Carbon::now();
            if ($failedCount > 0 && $publishedCount === 0) {
                $socialCard->update([
                    'status' => BrandsSocialCard::STATUS_FAILED,
                ]);
            } elseif ($failedCount > 0) {
                // Teilweise erfolgreich — als failed markieren
                $socialCard->update([
                    'status' => BrandsSocialCard::STATUS_FAILED,
                    'published_at' => $now,
                ]);
            } else {
                // Alle erfolgreich
                $socialCard->update([
                    'status' => BrandsSocialCard::STATUS_PUBLISHED,
                    'published_at' => $now,
                ]);
            }

            return ToolResult::success([
                'social_card_id' => $socialCard->id,
                'social_card_title' => $socialCard->title,
                'social_card_status' => $socialCard->fresh()->status,
                'published_at' => $socialCard->fresh()->published_at?->toIso8601String(),
                'results' => $results,
                'published_count' => $publishedCount,
                'failed_count' => $failedCount,
                'total_count' => count($results),
                'message' => $failedCount === 0
                    ? "Alle {$publishedCount} Contract(s) erfolgreich gepublished."
                    : "{$publishedCount} erfolgreich, {$failedCount} fehlgeschlagen.",
            ]);
        } catch (\Throwable $e) {
            // Bei unerwarteten Fehlern: Card auf failed setzen
            if (isset($socialCard)) {
                $socialCard->update(['status' => BrandsSocialCard::STATUS_FAILED]);
            }
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Publishing: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'social_card', 'publish', 'meta', 'facebook', 'instagram'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'high',
            'idempotent' => false,
            'side_effects' => ['updates', 'external_api_call'],
        ];
    }
}
