<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Illuminate\Support\Facades\Gate;

class ListCompetitorBoardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.competitor_boards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/{brand_id}/competitor_boards - Listet Wettbewerber Boards einer Marke auf. REST-Parameter: brand_id (required, integer) - Marken-ID.';
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

            $query = BrandsCompetitorBoard::query()
                ->where('brand_id', $brandId)
                ->with(['brand', 'user', 'team', 'competitors']);

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

            $boardsList = $boards->map(function ($board) {
                return [
                    'id' => $board->id,
                    'uuid' => $board->uuid,
                    'name' => $board->name,
                    'description' => $board->description,
                    'brand_id' => $board->brand_id,
                    'brand_name' => $board->brand->name,
                    'team_id' => $board->team_id,
                    'competitors_count' => $board->competitors->count(),
                    'done' => $board->done,
                    'done_at' => $board->done_at?->toIso8601String(),
                    'created_at' => $board->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'competitor_boards' => $boardsList,
                'count' => count($boardsList),
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'message' => count($boardsList) > 0
                    ? count($boardsList) . ' Wettbewerber Board(s) gefunden für Marke "' . $brand->name . '".'
                    : 'Keine Wettbewerber Boards gefunden für Marke "' . $brand->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Wettbewerber Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'competitor_board', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
