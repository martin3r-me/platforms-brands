<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsToneOfVoiceEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteToneOfVoiceEntryTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.tone_of_voice_entries.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/tone_of_voice_entries/{id} - Löscht einen Messaging-Eintrag. REST-Parameter: entry_id (required, integer) - Eintrag-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entry_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Messaging-Eintrags (ERFORDERLICH). Nutze "brands.tone_of_voice_entries.GET" um Einträge zu finden.'
                ],
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
                Gate::forUser($context->user)->authorize('delete', $entry);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Messaging-Eintrag nicht löschen.');
            }

            $entryName = $entry->name;
            $entryId = $entry->id;
            $boardId = $entry->tone_of_voice_board_id;

            $entry->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.tone_of_voice_entries.GET', $context->user->id, $context->user->current_team_id);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'entry_id' => $entryId,
                'entry_name' => $entryName,
                'tone_of_voice_board_id' => $boardId,
                'message' => "Messaging-Eintrag '{$entryName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Messaging-Eintrags: ' . $e->getMessage());
        }
    }
}
