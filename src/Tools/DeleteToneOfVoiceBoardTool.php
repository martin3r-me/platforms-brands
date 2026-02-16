<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteToneOfVoiceBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.tone_of_voice_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/tone_of_voice_boards/{id} - Löscht ein Tone of Voice Board. REST-Parameter: tone_of_voice_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tone_of_voice_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Tone of Voice Boards (ERFORDERLICH). Nutze "brands.tone_of_voice_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['tone_of_voice_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'tone_of_voice_board_id',
                BrandsToneOfVoiceBoard::class,
                'TONE_OF_VOICE_BOARD_NOT_FOUND',
                'Das angegebene Tone of Voice Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $board = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Tone of Voice Board nicht löschen (Policy).');
            }

            $boardName = $board->name;
            $boardId = $board->id;
            $brandId = $board->brand_id;
            $teamId = $board->team_id;

            $board->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.tone_of_voice_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'tone_of_voice_board_id' => $boardId,
                'tone_of_voice_board_name' => $boardName,
                'brand_id' => $brandId,
                'message' => "Tone of Voice Board '{$boardName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Tone of Voice Boards: ' . $e->getMessage());
        }
    }
}
