<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsPersonaBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeletePersonaBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.persona_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/persona_boards/{id} - Löscht ein Persona Board. REST-Parameter: persona_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'persona_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Persona Boards (ERFORDERLICH). Nutze "brands.persona_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['persona_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'persona_board_id',
                BrandsPersonaBoard::class,
                'PERSONA_BOARD_NOT_FOUND',
                'Das angegebene Persona Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $board = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Persona Board nicht löschen (Policy).');
            }

            $boardName = $board->name;
            $boardId = $board->id;
            $brandId = $board->brand_id;
            $teamId = $board->team_id;

            $board->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.persona_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'persona_board_id' => $boardId,
                'persona_board_name' => $boardName,
                'brand_id' => $brandId,
                'message' => "Persona Board '{$boardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Persona Boards: ' . $e->getMessage());
        }
    }
}
