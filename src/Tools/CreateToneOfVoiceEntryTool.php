<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Platform\Brands\Models\BrandsToneOfVoiceEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateToneOfVoiceEntryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.tone_of_voice_entries.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/tone_of_voice_boards/{tone_of_voice_board_id}/entries - Erstellt einen neuen Messaging-Eintrag (Slogan, Elevator Pitch, Kernbotschaft, Wert, Claim). REST-Parameter: tone_of_voice_board_id (required), name (required), type (required: slogan, elevator_pitch, core_message, value, claim), content (required), description (optional), example_positive (optional, "So ja"-Beispiel), example_negative (optional, "So nein"-Beispiel).';
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
                    'description' => 'Name des Eintrags (ERFORDERLICH), z.B. "Haupt-Slogan", "Elevator Pitch Q1".'
                ],
                'type' => [
                    'type' => 'string',
                    'description' => 'Typ des Messaging-Elements (ERFORDERLICH).',
                    'enum' => ['slogan', 'elevator_pitch', 'core_message', 'value', 'claim']
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Der eigentliche Text/Inhalt des Messaging-Elements (ERFORDERLICH).'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Kontext oder Erklärung zum Eintrag.'
                ],
                'example_positive' => [
                    'type' => 'string',
                    'description' => '"So ja"-Beispieltext: Wie die Markenstimme klingen SOLL.'
                ],
                'example_negative' => [
                    'type' => 'string',
                    'description' => '"So nein"-Beispieltext: Wie die Markenstimme NICHT klingen soll.'
                ],
            ],
            'required' => ['tone_of_voice_board_id', 'name', 'type', 'content']
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Einträge für dieses Board erstellen.');
            }

            $name = $arguments['name'] ?? null;
            if (!$name) {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $type = $arguments['type'] ?? null;
            if (!$type || !array_key_exists($type, BrandsToneOfVoiceEntry::TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', 'type ist erforderlich und muss einer der folgenden sein: ' . implode(', ', array_keys(BrandsToneOfVoiceEntry::TYPES)));
            }

            $content = $arguments['content'] ?? null;
            if (!$content) {
                return ToolResult::error('VALIDATION_ERROR', 'content ist erforderlich.');
            }

            $entry = BrandsToneOfVoiceEntry::create([
                'tone_of_voice_board_id' => $board->id,
                'name' => $name,
                'type' => $type,
                'content' => $content,
                'description' => $arguments['description'] ?? null,
                'example_positive' => $arguments['example_positive'] ?? null,
                'example_negative' => $arguments['example_negative'] ?? null,
            ]);

            $entry->load('toneOfVoiceBoard');

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'name' => $entry->name,
                'type' => $entry->type,
                'type_label' => $entry->type_label,
                'content' => $entry->content,
                'description' => $entry->description,
                'example_positive' => $entry->example_positive,
                'example_negative' => $entry->example_negative,
                'order' => $entry->order,
                'tone_of_voice_board_id' => $entry->tone_of_voice_board_id,
                'created_at' => $entry->created_at->toIso8601String(),
                'message' => "Messaging-Eintrag '{$entry->name}' ({$entry->type_label}) erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Messaging-Eintrags: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'tone_of_voice_entry', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
