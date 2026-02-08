<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsKanbanBoard;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von KanbanBoards im Brands-Modul
 */
class ListKanbanBoardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.kanban_boards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/{brand_id}/kanban_boards - Listet Kanban Boards einer Marke auf. REST-Parameter: brand_id (required, integer) - Marken-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
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

            // Query aufbauen - Kanban Boards
            $query = BrandsKanbanBoard::query()
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
            $boardsList = $boards->map(function($kanbanBoard) {
                return [
                    'id' => $kanbanBoard->id,
                    'uuid' => $kanbanBoard->uuid,
                    'name' => $kanbanBoard->name,
                    'description' => $kanbanBoard->description,
                    'brand_id' => $kanbanBoard->brand_id,
                    'brand_name' => $kanbanBoard->brand->name,
                    'team_id' => $kanbanBoard->team_id,
                    'user_id' => $kanbanBoard->user_id,
                    'done' => $kanbanBoard->done,
                    'done_at' => $kanbanBoard->done_at?->toIso8601String(),
                    'created_at' => $kanbanBoard->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'kanban_boards' => $boardsList,
                'count' => count($boardsList),
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'message' => count($boardsList) > 0
                    ? count($boardsList) . ' Kanban Board(s) gefunden für Marke "' . $brand->name . '".'
                    : 'Keine Kanban Boards gefunden für Marke "' . $brand->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Kanban Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'kanban_board', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
