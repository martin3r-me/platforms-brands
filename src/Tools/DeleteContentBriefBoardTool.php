<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteContentBriefBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_brief_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/content_brief_boards/{id} - Löscht ein Content Brief Board. REST-Parameter: content_brief_board_id (required, integer) - Content Brief Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Content Brief Boards (ERFORDERLICH).'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass das Board wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['content_brief_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'content_brief_board_id', BrandsContentBriefBoard::class,
                'CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $board = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Content Brief Board nicht löschen (Policy).');
            }

            $boardName = $board->name;
            $boardId = $board->id;
            $brandId = $board->brand_id;
            $teamId = $board->team_id;

            $board->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.content_brief_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'content_brief_board_id' => $boardId,
                'content_brief_board_name' => $boardName,
                'brand_id' => $brandId,
                'message' => "Content Brief Board '{$boardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Content Brief Boards: ' . $e->getMessage());
        }
    }
}
