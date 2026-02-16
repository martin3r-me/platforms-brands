<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsTypographyEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteTypographyEntryTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.typography_entries.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/typography_entries/{id} - Löscht einen Typografie-Eintrag. REST-Parameter: entry_id (required, integer) - Eintrag-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entry_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Typografie-Eintrags (ERFORDERLICH). Nutze "brands.typography_entries.GET" um Einträge zu finden.'
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
                BrandsTypographyEntry::class,
                'ENTRY_NOT_FOUND',
                'Der angegebene Typografie-Eintrag wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $entry = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $entry);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Typografie-Eintrag nicht löschen.');
            }

            $entryName = $entry->name;
            $entryId = $entry->id;
            $boardId = $entry->typography_board_id;

            $entry->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.typography_entries.GET', $context->user->id, $context->user->current_team_id);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'entry_id' => $entryId,
                'entry_name' => $entryName,
                'typography_board_id' => $boardId,
                'message' => "Typografie-Eintrag '{$entryName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Typografie-Eintrags: ' . $e->getMessage());
        }
    }
}
