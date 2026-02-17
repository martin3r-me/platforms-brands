<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsAssetBoard;
use Platform\Brands\Models\BrandsAsset;
use Platform\Brands\Models\BrandsAssetVersion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateAssetTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.assets.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/asset_boards/{asset_board_id}/assets - Erstellt ein neues Asset in einem Asset Board. REST-Parameter: asset_board_id (required), file_path (required), name (optional), description (optional), asset_type (optional), tags (optional, array), available_formats (optional, array).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'asset_board_id' => ['type' => 'integer', 'description' => 'ID des Asset Boards (ERFORDERLICH).'],
                'file_path' => ['type' => 'string', 'description' => 'Pfad zur Datei (ERFORDERLICH).'],
                'name' => ['type' => 'string', 'description' => 'Name des Assets.'],
                'description' => ['type' => 'string', 'description' => 'Beschreibung des Assets.'],
                'asset_type' => [
                    'type' => 'string',
                    'description' => 'Typ des Assets: "sm_template" (Social Media Template), "letterhead" (Briefkopf), "signature" (E-Mail-Signatur), "banner", "presentation" (Präsentation), "other" (Sonstiges, Standard).',
                    'enum' => ['sm_template', 'letterhead', 'signature', 'banner', 'presentation', 'other'],
                ],
                'tags' => [
                    'type' => 'array',
                    'description' => 'Kanal-Tags für das Asset, z.B. ["Instagram", "LinkedIn", "Print", "Web"].',
                    'items' => ['type' => 'string'],
                ],
                'available_formats' => [
                    'type' => 'array',
                    'description' => 'Verfügbare Download-Formate, z.B. ["png", "svg", "pdf"].',
                    'items' => ['type' => 'string'],
                ],
                'file_name' => ['type' => 'string', 'description' => 'Originaler Dateiname.'],
                'mime_type' => ['type' => 'string', 'description' => 'MIME-Type der Datei.'],
                'file_size' => ['type' => 'integer', 'description' => 'Dateigröße in Bytes.'],
            ],
            'required' => ['asset_board_id', 'file_path']
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

            $board = BrandsAssetBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('ASSET_BOARD_NOT_FOUND', 'Das angegebene Asset Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Assets für dieses Board erstellen.');
            }

            $filePath = $arguments['file_path'] ?? null;
            if (!$filePath) {
                return ToolResult::error('VALIDATION_ERROR', 'file_path ist erforderlich.');
            }

            $asset = BrandsAsset::create([
                'asset_board_id' => $board->id,
                'name' => $arguments['name'] ?? 'Neues Asset',
                'description' => $arguments['description'] ?? null,
                'asset_type' => $arguments['asset_type'] ?? 'other',
                'file_path' => $filePath,
                'file_name' => $arguments['file_name'] ?? null,
                'mime_type' => $arguments['mime_type'] ?? null,
                'file_size' => $arguments['file_size'] ?? null,
                'tags' => $arguments['tags'] ?? null,
                'available_formats' => $arguments['available_formats'] ?? null,
                'current_version' => 1,
            ]);

            // Erste Version anlegen
            BrandsAssetVersion::create([
                'asset_id' => $asset->id,
                'version_number' => 1,
                'file_path' => $filePath,
                'file_name' => $arguments['file_name'] ?? null,
                'mime_type' => $arguments['mime_type'] ?? null,
                'file_size' => $arguments['file_size'] ?? null,
                'change_note' => 'Erstversion',
                'user_id' => $context->user->id,
            ]);

            $asset->load('assetBoard');

            return ToolResult::success([
                'id' => $asset->id,
                'uuid' => $asset->uuid,
                'name' => $asset->name,
                'description' => $asset->description,
                'asset_type' => $asset->asset_type,
                'file_path' => $asset->file_path,
                'file_name' => $asset->file_name,
                'tags' => $asset->tags,
                'available_formats' => $asset->available_formats,
                'current_version' => $asset->current_version,
                'order' => $asset->order,
                'asset_board_id' => $asset->asset_board_id,
                'created_at' => $asset->created_at->toIso8601String(),
                'message' => "Asset '{$asset->name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Assets: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'asset', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
