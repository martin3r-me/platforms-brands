<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Platform\Brands\Models\BrandsToneOfVoiceDimension;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateToneOfVoiceDimensionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.tone_of_voice_dimensions.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/tone_of_voice_boards/{tone_of_voice_board_id}/dimensions - Erstellt eine neue Tone-Dimension (z.B. formell ↔ locker). REST-Parameter: tone_of_voice_board_id (required), name (required), label_left (required), label_right (required), value (optional, 0-100, Standard: 50), description (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tone_of_voice_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Tone of Voice Boards (ERFORDERLICH).'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Dimension (ERFORDERLICH), z.B. "Formalität", "Humor", "Komplexität".'
                ],
                'label_left' => [
                    'type' => 'string',
                    'description' => 'Linkes Label der Skala (ERFORDERLICH), z.B. "Formell", "Ernst", "Technisch".'
                ],
                'label_right' => [
                    'type' => 'string',
                    'description' => 'Rechtes Label der Skala (ERFORDERLICH), z.B. "Locker", "Humorvoll", "Einfach".'
                ],
                'value' => [
                    'type' => 'integer',
                    'description' => 'Position auf der Skala (0-100, 50 = Mitte). Standard: 50.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung der Dimension.'
                ],
            ],
            'required' => ['tone_of_voice_board_id', 'name', 'label_left', 'label_right']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $boardId = $arguments['tone_of_voice_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'tone_of_voice_board_id ist erforderlich.');
            }

            $board = BrandsToneOfVoiceBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('TONE_OF_VOICE_BOARD_NOT_FOUND', 'Das angegebene Tone of Voice Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Dimensionen für dieses Board erstellen.');
            }

            foreach (['name', 'label_left', 'label_right'] as $required) {
                if (empty($arguments[$required])) {
                    return ToolResult::error('VALIDATION_ERROR', "{$required} ist erforderlich.");
                }
            }

            $value = isset($arguments['value']) ? max(0, min(100, (int) $arguments['value'])) : 50;

            $dimension = BrandsToneOfVoiceDimension::create([
                'tone_of_voice_board_id' => $board->id,
                'name' => $arguments['name'],
                'label_left' => $arguments['label_left'],
                'label_right' => $arguments['label_right'],
                'value' => $value,
                'description' => $arguments['description'] ?? null,
            ]);

            $dimension->load('toneOfVoiceBoard');

            return ToolResult::success([
                'id' => $dimension->id,
                'uuid' => $dimension->uuid,
                'name' => $dimension->name,
                'label_left' => $dimension->label_left,
                'label_right' => $dimension->label_right,
                'value' => $dimension->value,
                'description' => $dimension->description,
                'order' => $dimension->order,
                'tone_of_voice_board_id' => $dimension->tone_of_voice_board_id,
                'created_at' => $dimension->created_at->toIso8601String(),
                'message' => "Tone-Dimension '{$dimension->name}' ({$dimension->label_left} ↔ {$dimension->label_right}) erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Tone-Dimension: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'tone_of_voice_dimension', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
