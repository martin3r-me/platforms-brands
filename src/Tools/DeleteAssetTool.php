<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsAsset;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteAssetTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.assets.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/assets/{id} - Löscht ein Asset inkl. aller Versionen. REST-Parameter: asset_id (required, integer) - Asset-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'asset_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Assets (ERFORDERLICH).'
                ],
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Asset nicht löschen.');
            }

            $assetId = $asset->id;
            $assetName = $asset->name;
            $boardId = $board->id;

            $asset->delete();

            return ToolResult::success([
                'asset_id' => $assetId,
                'asset_name' => $assetName,
                'asset_board_id' => $boardId,
                'message' => "Asset '{$assetName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Assets: ' . $e->getMessage());
        }
    }
}
