<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsAsset;
use Platform\Brands\Models\BrandsAssetVersion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateAssetTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.assets.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/assets/{id} - Aktualisiert ein Asset. Bei neuem file_path wird automatisch eine neue Version erstellt. REST-Parameter: asset_id (required), name (optional), description (optional), asset_type (optional), tags (optional, array), available_formats (optional, array), file_path (optional, erstellt neue Version).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'asset_id' => ['type' => 'integer', 'description' => 'ID des Assets (ERFORDERLICH).'],
                'name' => ['type' => 'string', 'description' => 'Optional: Name des Assets.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                'asset_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Typ des Assets.',
                    'enum' => ['sm_template', 'letterhead', 'signature', 'banner', 'presentation', 'other'],
                ],
                'tags' => [
                    'type' => 'array',
                    'description' => 'Optional: Kanal-Tags.',
                    'items' => ['type' => 'string'],
                ],
                'available_formats' => [
                    'type' => 'array',
                    'description' => 'Optional: Verfügbare Download-Formate.',
                    'items' => ['type' => 'string'],
                ],
                'file_path' => ['type' => 'string', 'description' => 'Optional: Neuer Dateipfad (erstellt neue Version).'],
                'file_name' => ['type' => 'string', 'description' => 'Optional: Neuer Dateiname.'],
                'mime_type' => ['type' => 'string', 'description' => 'Optional: Neuer MIME-Type.'],
                'file_size' => ['type' => 'integer', 'description' => 'Optional: Neue Dateigröße in Bytes.'],
                'change_note' => ['type' => 'string', 'description' => 'Optional: Änderungsnotiz für neue Version.'],
            ],
            'required' => ['asset_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'asset_id',
                BrandsAsset::class, 'ASSET_NOT_FOUND',
                'Das angegebene Asset wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $asset = $validation['model'];
            $board = $asset->assetBoard;

            if (!$board) {
                return ToolResult::error('BOARD_NOT_FOUND', 'Das zugehörige Asset Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Asset nicht bearbeiten.');
            }

            $updateData = [];
            if (isset($arguments['name'])) $updateData['name'] = $arguments['name'];
            if (isset($arguments['description'])) $updateData['description'] = $arguments['description'];
            if (isset($arguments['asset_type'])) $updateData['asset_type'] = $arguments['asset_type'];
            if (isset($arguments['tags'])) $updateData['tags'] = $arguments['tags'];
            if (isset($arguments['available_formats'])) $updateData['available_formats'] = $arguments['available_formats'];

            // Neue Datei = neue Version
            if (isset($arguments['file_path'])) {
                $newVersion = $asset->current_version + 1;

                BrandsAssetVersion::create([
                    'asset_id' => $asset->id,
                    'version_number' => $newVersion,
                    'file_path' => $arguments['file_path'],
                    'file_name' => $arguments['file_name'] ?? $asset->file_name,
                    'mime_type' => $arguments['mime_type'] ?? $asset->mime_type,
                    'file_size' => $arguments['file_size'] ?? $asset->file_size,
                    'change_note' => $arguments['change_note'] ?? null,
                    'user_id' => $context->user->id,
                ]);

                $updateData['file_path'] = $arguments['file_path'];
                $updateData['current_version'] = $newVersion;
                if (isset($arguments['file_name'])) $updateData['file_name'] = $arguments['file_name'];
                if (isset($arguments['mime_type'])) $updateData['mime_type'] = $arguments['mime_type'];
                if (isset($arguments['file_size'])) $updateData['file_size'] = $arguments['file_size'];
            }

            if (!empty($updateData)) {
                $asset->update($updateData);
            }

            $asset->refresh();

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
                'updated_at' => $asset->updated_at->toIso8601String(),
                'message' => "Asset '{$asset->name}' erfolgreich aktualisiert." . (isset($arguments['file_path']) ? " Neue Version v{$asset->current_version} erstellt." : '')
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Assets: ' . $e->getMessage());
        }
    }
}
