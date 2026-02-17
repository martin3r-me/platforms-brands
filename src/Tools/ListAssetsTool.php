<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsAssetBoard;
use Platform\Brands\Models\BrandsAsset;
use Illuminate\Support\Facades\Gate;

class ListAssetsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.assets.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/asset_boards/{asset_board_id}/assets - Listet Assets eines Asset Boards auf. REST-Parameter: asset_board_id (required, integer). filters (optional). sort (optional). limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'asset_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Asset Boards.'
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

            $boardId = $arguments['asset_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'asset_board_id ist erforderlich.');
            }

            $board = BrandsAssetBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('ASSET_BOARD_NOT_FOUND', 'Das angegebene Asset Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Asset Board.');
            }

            $query = BrandsAsset::query()
                ->where('asset_board_id', $boardId);

            $this->applyStandardFilters($query, $arguments, [
                'name', 'asset_type', 'created_at', 'updated_at'
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'name', 'asset_type', 'created_at', 'updated_at', 'order'
            ], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $assets = $query->get();

            $assetsList = $assets->map(function ($asset) {
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
                    'order' => $asset->order,
                    'created_at' => $asset->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'assets' => $assetsList,
                'count' => count($assetsList),
                'asset_board_id' => $boardId,
                'asset_board_name' => $board->name,
                'message' => count($assetsList) > 0
                    ? count($assetsList) . ' Asset(s) im Board "' . $board->name . '" gefunden.'
                    : 'Keine Assets im Board "' . $board->name . '" gefunden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Assets: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'asset', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
