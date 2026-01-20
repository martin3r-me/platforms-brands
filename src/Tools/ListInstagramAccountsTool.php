<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsBrand;
use Platform\Integrations\Services\IntegrationAccountLinkService;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von verknüpften Instagram Accounts einer Marke
 */
class ListInstagramAccountsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.instagram_accounts.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/{brand_id}/instagram_accounts - Listet verknüpfte Instagram Accounts einer Marke auf. REST-Parameter: brand_id (required, integer) - Marken-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'brand_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID der Marke. Nutze "brands.brands.GET" um Marken zu finden.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $brandId = $arguments['brand_id'] ?? null;
            if (!$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'brand_id ist erforderlich.');
            }

            $brand = BrandsBrand::find($brandId);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Marke wurde nicht gefunden.');
            }

            // Policy prüfen
            if (!Gate::forUser($context->user)->allows('view', $brand)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Marke.');
            }

            // Verknüpfte Instagram Accounts holen
            $linkService = app(IntegrationAccountLinkService::class);
            $instagramAccounts = $linkService->getLinkedInstagramAccounts($brand);

            // Collection in Query umwandeln für Filter/Suche/Sortierung
            $query = \Platform\Integrations\Models\IntegrationsInstagramAccount::query()
                ->whereIn('id', $instagramAccounts->pluck('id')->toArray());

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'username', 'description', 'external_id', 'created_at', 'updated_at'
            ]);
            
            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['username', 'description']);
            
            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'username', 'created_at', 'updated_at'
            ], 'username', 'asc');
            
            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            $accounts = $query->get();

            // Accounts formatieren
            $accountsList = $accounts->map(function($account) {
                return [
                    'id' => $account->id,
                    'uuid' => $account->uuid,
                    'username' => $account->username,
                    'description' => $account->description,
                    'external_id' => $account->external_id,
                    'facebook_page_id' => $account->facebook_page_id,
                    'user_id' => $account->user_id,
                    'created_at' => $account->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'instagram_accounts' => $accountsList,
                'count' => count($accountsList),
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'message' => count($accountsList) > 0 
                    ? count($accountsList) . ' verknüpfte Instagram Account(s) gefunden für Marke "' . $brand->name . '".'
                    : 'Keine verknüpften Instagram Accounts gefunden für Marke "' . $brand->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Instagram Accounts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'instagram_account', 'list', 'social_media'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
