<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsGuidelineEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteGuidelineEntryTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.guideline_entries.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/guideline_entries/{id} - Löscht einen Guideline-Eintrag. REST-Parameter: entry_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entry_id' => ['type' => 'integer', 'description' => 'ID des zu löschenden Guideline-Eintrags (ERFORDERLICH).'],
            ],
            'required' => ['entry_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'entry_id',
                BrandsGuidelineEntry::class, 'ENTRY_NOT_FOUND',
                'Der angegebene Guideline-Eintrag wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $entry = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $entry);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Guideline-Eintrag nicht löschen.');
            }

            $entryTitle = $entry->title;
            $entryId = $entry->id;
            $chapterId = $entry->guideline_chapter_id;

            $entry->delete();

            return ToolResult::success([
                'entry_id' => $entryId,
                'entry_title' => $entryTitle,
                'guideline_chapter_id' => $chapterId,
                'message' => "Guideline-Eintrag '{$entryTitle}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Guideline-Eintrags: ' . $e->getMessage());
        }
    }
}
