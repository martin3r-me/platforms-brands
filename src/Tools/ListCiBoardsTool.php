<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCiBoard;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von CiBoards im Brands-Modul
 */
class ListCiBoardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.ci_boards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/{brand_id}/ci_boards - Listet CI Boards einer Marke auf. REST-Parameter: brand_id (required, integer) - Marken-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
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
            
            // Query aufbauen - CI Boards
            $query = BrandsCiBoard::query()
                ->where('brand_id', $brandId)
                ->with(['brand', 'user', 'team']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'done', 'created_at', 'updated_at'
            ]);
            
            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            
            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'name', 'created_at', 'updated_at', 'order'
            ], 'order', 'asc');
            
            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            // Boards holen und per Policy filtern
            $boards = $query->get()->filter(function ($board) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $board);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            // Boards formatieren
            $boardsList = $boards->map(function($ciBoard) {
                return [
                    'id' => $ciBoard->id,
                    'uuid' => $ciBoard->uuid,
                    'name' => $ciBoard->name,
                    'description' => $ciBoard->description,
                    'brand_id' => $ciBoard->brand_id,
                    'brand_name' => $ciBoard->brand->name,
                    'team_id' => $ciBoard->team_id,
                    'user_id' => $ciBoard->user_id,
                    'done' => $ciBoard->done,
                    'done_at' => $ciBoard->done_at?->toIso8601String(),
                    'created_at' => $ciBoard->created_at->toIso8601String(),
                    'primary_color' => $ciBoard->primary_color,
                    'secondary_color' => $ciBoard->secondary_color,
                    'accent_color' => $ciBoard->accent_color,
                    'slogan' => $ciBoard->slogan,
                    'font_family' => $ciBoard->font_family,
                    'tagline' => $ciBoard->tagline,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'ci_boards' => $boardsList,
                'count' => count($boardsList),
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'message' => count($boardsList) > 0 
                    ? count($boardsList) . ' CI Board(s) gefunden für Marke "' . $brand->name . '".'
                    : 'Keine CI Boards gefunden für Marke "' . $brand->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der CI Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'ci_board', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
