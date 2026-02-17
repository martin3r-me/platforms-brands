<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsAsset;
use Illuminate\Support\Facades\Gate;

class GetAssetTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.asset.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/assets/{id} - Gibt ein einzelnes Asset zurück inkl. Versionshistorie. REST-Parameter: asset_id (required, integer) - Asset-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'asset_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Assets (ERFORDERLICH).'
                ],
            ],
            'required' => ['asset_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $assetId = $arguments['asset_id'] ?? null;
            if (!$assetId) {
                return ToolResult::error('VALIDATION_ERROR', 'asset_id ist erforderlich.');
            }

            $asset = BrandsAsset::with(['assetBoard.brand', 'versions'])->find($assetId);
            if (!$asset) {
                return ToolResult::error('ASSET_NOT_FOUND', 'Das angegebene Asset wurde nicht gefunden.');
            }

            $board = $asset->assetBoard;
            if (!$board || !Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Asset.');
            }

            $versions = $asset->versions->map(function ($version) {
                return [
                    'id' => $version->id,
                    'uuid' => $version->uuid,
                    'version_number' => $version->version_number,
                    'file_path' => $version->file_path,
                    'file_name' => $version->file_name,
                    'file_size' => $version->file_size,
                    'change_note' => $version->change_note,
                    'created_at' => $version->created_at->toIso8601String(),
                ];
            })->toArray();

            return ToolResult::success([
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
                'versions' => $versions,
                'versions_count' => count($versions),
                'order' => $asset->order,
                'asset_board_id' => $asset->asset_board_id,
                'asset_board_name' => $board->name,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name ?? null,
                'created_at' => $asset->created_at->toIso8601String(),
                'message' => "Asset '{$asset->name}' mit " . count($versions) . " Version(en) geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Assets: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'asset', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
