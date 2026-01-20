<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsMultiContentBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen von MultiContentBoards im Brands-Modul
 */
class DeleteMultiContentBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.multi_content_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/multi_content_boards/{id} - Löscht ein Multi-Content-Board. REST-Parameter: multi_content_board_id (required, integer) - Multi-Content-Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'multi_content_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Multi-Content-Boards (ERFORDERLICH). Nutze "brands.multi_content_boards.GET" um Multi-Content-Boards zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass das Multi-Content-Board wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['multi_content_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'multi_content_board_id',
                BrandsMultiContentBoard::class,
                'MULTI_CONTENT_BOARD_NOT_FOUND',
                'Das angegebene Multi-Content-Board wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $multiContentBoard = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $multiContentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Multi-Content-Board nicht löschen (Policy).');
            }

            $multiContentBoardName = $multiContentBoard->name;
            $multiContentBoardId = $multiContentBoard->id;
            $brandId = $multiContentBoard->brand_id;
            $teamId = $multiContentBoard->team_id;

            // MultiContentBoard löschen
            $multiContentBoard->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.multi_content_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'multi_content_board_id' => $multiContentBoardId,
                'multi_content_board_name' => $multiContentBoardName,
                'brand_id' => $brandId,
                'message' => "Multi-Content-Board '{$multiContentBoardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Multi-Content-Boards: ' . $e->getMessage());
        }
    }
}
