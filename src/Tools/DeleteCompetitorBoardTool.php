<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteCompetitorBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.competitor_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/competitor_boards/{id} - Löscht ein Wettbewerber Board. REST-Parameter: competitor_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'competitor_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Wettbewerber Boards (ERFORDERLICH). Nutze "brands.competitor_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['competitor_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'competitor_board_id',
                BrandsCompetitorBoard::class,
                'COMPETITOR_BOARD_NOT_FOUND',
                'Das angegebene Wettbewerber Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $board = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Wettbewerber Board nicht löschen (Policy).');
            }

            $boardName = $board->name;
            $boardId = $board->id;
            $brandId = $board->brand_id;
            $teamId = $board->team_id;

            $board->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.competitor_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'competitor_board_id' => $boardId,
                'competitor_board_name' => $boardName,
                'brand_id' => $brandId,
                'message' => "Wettbewerber Board '{$boardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Wettbewerber Boards: ' . $e->getMessage());
        }
    }
}
