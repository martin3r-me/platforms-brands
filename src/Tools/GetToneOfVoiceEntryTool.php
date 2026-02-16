<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsToneOfVoiceEntry;
use Illuminate\Support\Facades\Gate;

class GetToneOfVoiceEntryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.tone_of_voice_entry.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/tone_of_voice_entries/{id} - Gibt einen einzelnen Messaging-Eintrag zurück. REST-Parameter: entry_id (required, integer) - Eintrag-ID.';
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
            ],
            'required' => ['entry_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $entryId = $arguments['entry_id'] ?? null;
            if (!$entryId) {
                return ToolResult::error('VALIDATION_ERROR', 'entry_id ist erforderlich.');
            }

            $entry = BrandsToneOfVoiceEntry::with('toneOfVoiceBoard')->find($entryId);
            if (!$entry) {
                return ToolResult::error('ENTRY_NOT_FOUND', 'Der angegebene Messaging-Eintrag wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $entry)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Eintrag.');
            }

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
                'tone_of_voice_board_name' => $entry->toneOfVoiceBoard->name,
                'created_at' => $entry->created_at->toIso8601String(),
                'updated_at' => $entry->updated_at->toIso8601String(),
                'message' => "Messaging-Eintrag '{$entry->name}' geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Messaging-Eintrags: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'tone_of_voice_entry', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
