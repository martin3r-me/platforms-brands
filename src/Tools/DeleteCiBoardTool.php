<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCiBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen von CiBoards im Brands-Modul
 */
class DeleteCiBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.ci_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/ci_boards/{id} - Löscht ein CI Board. REST-Parameter: ci_board_id (required, integer) - CI Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ci_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden CI Boards (ERFORDERLICH). Nutze "brands.ci_boards.GET" um CI Boards zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass das CI Board wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['ci_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'ci_board_id',
                BrandsCiBoard::class,
                'CI_BOARD_NOT_FOUND',
                'Das angegebene CI Board wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $ciBoard = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $ciBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses CI Board nicht löschen (Policy).');
            }

            $ciBoardName = $ciBoard->name;
            $ciBoardId = $ciBoard->id;
            $brandId = $ciBoard->brand_id;
            $teamId = $ciBoard->team_id;

            // CiBoard löschen
            $ciBoard->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.ci_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'ci_board_id' => $ciBoardId,
                'ci_board_name' => $ciBoardName,
                'brand_id' => $brandId,
                'message' => "CI Board '{$ciBoardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des CI Boards: ' . $e->getMessage());
        }
    }
}
