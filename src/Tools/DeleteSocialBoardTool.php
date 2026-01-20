<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSocialBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen von SocialBoards im Brands-Modul
 */
class DeleteSocialBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.social_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/social_boards/{id} - Löscht ein Social Board. REST-Parameter: social_board_id (required, integer) - Social Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'social_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Social Boards (ERFORDERLICH). Nutze "brands.social_boards.GET" um Social Boards zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass das Social Board wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['social_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'social_board_id',
                BrandsSocialBoard::class,
                'SOCIAL_BOARD_NOT_FOUND',
                'Das angegebene Social Board wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $socialBoard = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $socialBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Social Board nicht löschen (Policy).');
            }

            $socialBoardName = $socialBoard->name;
            $socialBoardId = $socialBoard->id;
            $brandId = $socialBoard->brand_id;
            $teamId = $socialBoard->team_id;

            // SocialBoard löschen
            $socialBoard->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.social_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'social_board_id' => $socialBoardId,
                'social_board_name' => $socialBoardName,
                'brand_id' => $brandId,
                'message' => "Social Board '{$socialBoardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Social Boards: ' . $e->getMessage());
        }
    }
}
