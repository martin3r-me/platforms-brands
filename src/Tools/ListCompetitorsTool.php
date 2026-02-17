<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Platform\Brands\Models\BrandsCompetitor;
use Illuminate\Support\Facades\Gate;

class ListCompetitorsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.competitors.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/competitor_boards/{competitor_board_id}/competitors - Listet Wettbewerber eines Boards auf. REST-Parameter: competitor_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'competitor_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Competitor Boards. Nutze "brands.competitor_boards.GET" um Boards zu finden.'
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

            $boardId = $arguments['competitor_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'competitor_board_id ist erforderlich.');
            }

            $board = BrandsCompetitorBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('COMPETITOR_BOARD_NOT_FOUND', 'Das angegebene Competitor Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Board.');
            }

            $query = BrandsCompetitor::query()
                ->where('competitor_board_id', $boardId)
                ->with(['competitorBoard']);

            $this->applyStandardFilters($query, $arguments, [
                'name', 'is_own_brand', 'created_at', 'updated_at'
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'description', 'notes']);
            $this->applyStandardSort($query, $arguments, [
                'name', 'order', 'created_at', 'updated_at'
            ], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $competitors = $query->get();

            $competitorsList = $competitors->map(function ($competitor) {
                return [
                    'id' => $competitor->id,
                    'uuid' => $competitor->uuid,
                    'name' => $competitor->name,
                    'logo_url' => $competitor->logo_url,
                    'website_url' => $competitor->website_url,
                    'description' => $competitor->description,
                    'strengths' => $competitor->strengths,
                    'weaknesses' => $competitor->weaknesses,
                    'notes' => $competitor->notes,
                    'position_x' => $competitor->position_x,
                    'position_y' => $competitor->position_y,
                    'is_own_brand' => $competitor->is_own_brand,
                    'differentiation' => $competitor->differentiation,
                    'order' => $competitor->order,
                    'competitor_board_id' => $competitor->competitor_board_id,
                    'created_at' => $competitor->created_at->toIso8601String(),
                    'updated_at' => $competitor->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'competitors' => $competitorsList,
                'count' => count($competitorsList),
                'competitor_board_id' => $boardId,
                'competitor_board_name' => $board->name,
                'message' => count($competitorsList) > 0
                    ? count($competitorsList) . ' Wettbewerber gefunden für Board "' . $board->name . '".'
                    : 'Keine Wettbewerber gefunden für Board "' . $board->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Wettbewerber: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'competitor', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
