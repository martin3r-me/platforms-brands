<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsAssetBoard;
use Illuminate\Support\Facades\Gate;

class GetAssetBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.asset_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/asset_boards/{id} - Gibt ein einzelnes Asset Board zurück inkl. aller Assets mit Tags, Typen und Versionen. REST-Parameter: asset_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'asset_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Asset Boards (ERFORDERLICH). Nutze "brands.asset_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['asset_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $boardId = $arguments['asset_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'asset_board_id ist erforderlich.');
            }

            $board = BrandsAssetBoard::with(['brand', 'assets.versions', 'user', 'team'])->find($boardId);
            if (!$board) {
                return ToolResult::error('ASSET_BOARD_NOT_FOUND', 'Das angegebene Asset Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Asset Board.');
            }

            $assets = $board->assets->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'uuid' => $asset->uuid,
                    'name' => $asset->name,
                    'description' => $asset->description,
                    'asset_type' => $asset->asset_type,
                    'file_path' => $asset->file_path,
                    'file_name' => $asset->file_name,
                    'mime_type' => $asset->mime_type,
                    'file_size' => $asset->file_size,
                    'tags' => $asset->tags,
                    'available_formats' => $asset->available_formats,
                    'current_version' => $asset->current_version,
                    'versions_count' => $asset->versions->count(),
                    'order' => $asset->order,
                ];
            })->toArray();

            $typeCounts = $board->assets->groupBy('asset_type')->map->count()->toArray();

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'team_id' => $board->team_id,
                'done' => $board->done,
                'assets' => $assets,
                'assets_count' => count($assets),
                'type_counts' => $typeCounts,
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Asset Board '{$board->name}' mit " . count($assets) . " Assets geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Asset Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'asset_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
