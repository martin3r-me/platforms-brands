<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Illuminate\Support\Facades\Gate;

class GetCompetitorBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.competitor_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/competitor_boards/{id} - Gibt ein einzelnes Wettbewerber Board zurück inkl. aller Wettbewerber. REST-Parameter: competitor_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'competitor_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Wettbewerber Boards (ERFORDERLICH). Nutze "brands.competitor_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['competitor_board_id']
        ];
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

            $board = BrandsCompetitorBoard::with(['brand', 'competitors', 'user', 'team'])->find($boardId);
            if (!$board) {
                return ToolResult::error('COMPETITOR_BOARD_NOT_FOUND', 'Das angegebene Wettbewerber Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Wettbewerber Board.');
            }

            $competitors = $board->competitors->map(function ($competitor) {
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
                ];
            })->toArray();

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'axis_x_label' => $board->axis_x_label,
                'axis_y_label' => $board->axis_y_label,
                'axis_x_min_label' => $board->axis_x_min_label,
                'axis_x_max_label' => $board->axis_x_max_label,
                'axis_y_min_label' => $board->axis_y_min_label,
                'axis_y_max_label' => $board->axis_y_max_label,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'team_id' => $board->team_id,
                'done' => $board->done,
                'competitors' => $competitors,
                'competitors_count' => count($competitors),
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Wettbewerber Board '{$board->name}' mit " . count($competitors) . " Wettbewerber(n) geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Wettbewerber Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'competitor_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
