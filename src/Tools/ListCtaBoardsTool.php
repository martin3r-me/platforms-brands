<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCtaBoard;
use Illuminate\Support\Facades\Gate;

class ListCtaBoardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.cta_boards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/{brand_id}/cta_boards - Listet CTA Boards einer Marke auf. REST-Parameter: brand_id (required, integer) - Marken-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
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

            if (!Gate::forUser($context->user)->allows('view', $brand)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Marke.');
            }

            $query = BrandsCtaBoard::query()
                ->where('brand_id', $brandId)
                ->with(['brand', 'user', 'team']);

            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'done', 'created_at', 'updated_at'
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'name', 'created_at', 'updated_at', 'order'
            ], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $boards = $query->get()->filter(function ($board) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $board);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            $boardsList = $boards->map(function ($ctaBoard) {
                return [
                    'id' => $ctaBoard->id,
                    'uuid' => $ctaBoard->uuid,
                    'name' => $ctaBoard->name,
                    'description' => $ctaBoard->description,
                    'brand_id' => $ctaBoard->brand_id,
                    'brand_name' => $ctaBoard->brand->name,
                    'team_id' => $ctaBoard->team_id,
                    'user_id' => $ctaBoard->user_id,
                    'done' => $ctaBoard->done,
                    'done_at' => $ctaBoard->done_at?->toIso8601String(),
                    'ctas_count' => $ctaBoard->ctas()->count(),
                    'created_at' => $ctaBoard->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'cta_boards' => $boardsList,
                'count' => count($boardsList),
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'message' => count($boardsList) > 0
                    ? count($boardsList) . ' CTA Board(s) gefunden für Marke "' . $brand->name . '".'
                    : 'Keine CTA Boards gefunden für Marke "' . $brand->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der CTA Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'cta_board', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
