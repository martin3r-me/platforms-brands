<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsAssetBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteAssetBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.asset_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/asset_boards/{id} - Löscht ein Asset Board inkl. aller Assets und Versionen. REST-Parameter: asset_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'asset_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Asset Boards (ERFORDERLICH). Nutze "brands.asset_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['asset_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'asset_board_id',
                BrandsAssetBoard::class, 'ASSET_BOARD_NOT_FOUND',
                'Das angegebene Asset Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $board = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Asset Board nicht löschen (Policy).');
            }

            $boardName = $board->name;
            $boardId = $board->id;
            $brandId = $board->brand_id;
            $teamId = $board->team_id;

            $board->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.asset_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'asset_board_id' => $boardId,
                'asset_board_name' => $boardName,
                'brand_id' => $brandId,
                'message' => "Asset Board '{$boardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Asset Boards: ' . $e->getMessage());
        }
    }
}
