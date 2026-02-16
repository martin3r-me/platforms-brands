<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateToneOfVoiceBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.tone_of_voice_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/tone_of_voice_boards/{id} - Aktualisiert ein Tone of Voice Board. REST-Parameter: tone_of_voice_board_id (required, integer) - Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. done (optional, boolean) - Als erledigt markieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tone_of_voice_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Tone of Voice Boards (ERFORDERLICH). Nutze "brands.tone_of_voice_boards.GET" um Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des Tone of Voice Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Tone of Voice Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Board als erledigt markieren.'
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
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Tone of Voice Board nicht bearbeiten (Policy).');
            }

            $updateData = [];
            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }
            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }
            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                $updateData['done_at'] = $arguments['done'] ? now() : null;
            }

            if (!empty($updateData)) {
                $board->update($updateData);
            }

            $board->refresh();
            $board->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'done' => $board->done,
                'done_at' => $board->done_at?->toIso8601String(),
                'updated_at' => $board->updated_at->toIso8601String(),
                'message' => "Tone of Voice Board '{$board->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Tone of Voice Boards: ' . $e->getMessage());
        }
    }
}
