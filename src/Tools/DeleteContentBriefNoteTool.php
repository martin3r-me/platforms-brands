<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBriefNote;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteContentBriefNoteTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_brief_notes.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/content_brief_notes/{id} - Löscht eine Briefing-Notiz. REST-Parameter: content_brief_note_id (required, integer) - Note-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_note_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Content Brief Note (ERFORDERLICH).'
                ],
            ],
            'required' => ['content_brief_note_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'content_brief_note_id', BrandsContentBriefNote::class,
                'CONTENT_BRIEF_NOTE_NOT_FOUND', 'Die angegebene Content Brief Note wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $note = $validation['model'];
            $note->load('contentBrief');

            // Authorization: user must be able to update the parent content brief
            try {
                Gate::forUser($context->user)->authorize('update', $note->contentBrief);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Content Brief Note nicht löschen (Policy).');
            }

            $noteId = $note->id;
            $noteType = $note->note_type;
            $contentBriefName = $note->contentBrief->name;

            $note->delete();

            return ToolResult::success([
                'content_brief_note_id' => $noteId,
                'note_type' => $noteType,
                'content_brief_name' => $contentBriefName,
                'message' => "Note ('{$noteType}') aus Content Brief '{$contentBriefName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Content Brief Note: ' . $e->getMessage());
        }
    }
}
