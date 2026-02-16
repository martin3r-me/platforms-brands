<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsToneOfVoiceEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateToneOfVoiceEntryTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.tone_of_voice_entries.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/tone_of_voice_entries/{id} - Aktualisiert einen Messaging-Eintrag. REST-Parameter: entry_id (required), name, type, content, description, example_positive, example_negative (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entry_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Messaging-Eintrags (ERFORDERLICH). Nutze "brands.tone_of_voice_entries.GET" um Einträge zu finden.'
                ],
                'name' => ['type' => 'string', 'description' => 'Optional: Name des Eintrags.'],
                'type' => [
                    'type' => 'string',
                    'description' => 'Optional: Typ des Messaging-Elements.',
                    'enum' => ['slogan', 'elevator_pitch', 'core_message', 'value', 'claim']
                ],
                'content' => ['type' => 'string', 'description' => 'Optional: Inhalt/Text.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Kontext/Beschreibung.'],
                'example_positive' => ['type' => 'string', 'description' => 'Optional: "So ja"-Beispieltext.'],
                'example_negative' => ['type' => 'string', 'description' => 'Optional: "So nein"-Beispieltext.'],
            ],
            'required' => ['entry_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'entry_id',
                BrandsToneOfVoiceEntry::class,
                'ENTRY_NOT_FOUND',
                'Der angegebene Messaging-Eintrag wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $entry = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $entry);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Messaging-Eintrag nicht bearbeiten.');
            }

            $fields = ['name', 'type', 'content', 'description', 'example_positive', 'example_negative'];
            $updateData = [];

            foreach ($fields as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $entry->update($updateData);
            }

            $entry->refresh();
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
                'updated_at' => $entry->updated_at->toIso8601String(),
                'message' => "Messaging-Eintrag '{$entry->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Messaging-Eintrags: ' . $e->getMessage());
        }
    }
}
