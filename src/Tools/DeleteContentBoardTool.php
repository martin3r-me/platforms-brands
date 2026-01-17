<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen von ContentBoards im Brands-Modul
 */
class DeleteContentBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/content_boards/{id} - Löscht ein Content Board. REST-Parameter: content_board_id (required, integer) - Content Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Content Boards (ERFORDERLICH). Nutze "brands.content_boards.GET" um Content Boards zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass das Content Board wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['content_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'content_board_id',
                BrandsContentBoard::class,
                'CONTENT_BOARD_NOT_FOUND',
                'Das angegebene Content Board wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $contentBoard = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Content Board nicht löschen (Policy).');
            }

            $contentBoardName = $contentBoard->name;
            $contentBoardId = $contentBoard->id;
            $brandId = $contentBoard->brand_id;
            $teamId = $contentBoard->team_id;

            // ContentBoard löschen
            $contentBoard->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.content_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'content_board_id' => $contentBoardId,
                'content_board_name' => $contentBoardName,
                'brand_id' => $brandId,
                'message' => "Content Board '{$contentBoardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Content Boards: ' . $e->getMessage());
        }
    }
}
