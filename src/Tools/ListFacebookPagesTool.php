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
 * Tool zum Auflisten von verknüpften Facebook Pages einer Marke
 */
class ListFacebookPagesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.facebook_pages.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/{brand_id}/facebook_pages - Listet verknüpfte Facebook Pages einer Marke auf. REST-Parameter: brand_id (required, integer) - Marken-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
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

            // Verknüpfte Facebook Pages holen
            $linkService = app(IntegrationAccountLinkService::class);
            $facebookPages = $linkService->getLinkedFacebookPages($brand);

            // Collection in Query umwandeln für Filter/Suche/Sortierung
            $query = \Platform\Integrations\Models\IntegrationsFacebookPage::query()
                ->whereIn('id', $facebookPages->pluck('id')->toArray());

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'external_id', 'created_at', 'updated_at'
            ]);
            
            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            
            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'name', 'created_at', 'updated_at'
            ], 'name', 'asc');
            
            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            $pages = $query->get();

            // Pages formatieren
            $pagesList = $pages->map(function($page) {
                return [
                    'id' => $page->id,
                    'uuid' => $page->uuid,
                    'name' => $page->name,
                    'description' => $page->description,
                    'external_id' => $page->external_id,
                    'user_id' => $page->user_id,
                    'created_at' => $page->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'facebook_pages' => $pagesList,
                'count' => count($pagesList),
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'message' => count($pagesList) > 0 
                    ? count($pagesList) . ' verknüpfte Facebook Page(s) gefunden für Marke "' . $brand->name . '".'
                    : 'Keine verknüpften Facebook Pages gefunden für Marke "' . $brand->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Facebook Pages: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'facebook_page', 'list', 'social_media'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
