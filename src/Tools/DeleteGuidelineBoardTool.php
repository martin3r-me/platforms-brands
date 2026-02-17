<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsGuidelineBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteGuidelineBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.guideline_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/guideline_boards/{id} - Löscht ein Guidelines Board. REST-Parameter: guideline_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'guideline_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Guidelines Boards (ERFORDERLICH). Nutze "brands.guideline_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['guideline_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'guideline_board_id',
                BrandsGuidelineBoard::class, 'GUIDELINE_BOARD_NOT_FOUND',
                'Das angegebene Guidelines Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $board = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Guidelines Board nicht löschen (Policy).');
            }

            $boardName = $board->name;
            $boardId = $board->id;
            $brandId = $board->brand_id;
            $teamId = $board->team_id;

            $board->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.guideline_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'guideline_board_id' => $boardId,
                'guideline_board_name' => $boardName,
                'brand_id' => $brandId,
                'message' => "Guidelines Board '{$boardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Guidelines Boards: ' . $e->getMessage());
        }
    }
}
