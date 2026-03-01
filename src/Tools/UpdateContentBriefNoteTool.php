<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBriefNote;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateContentBriefNoteTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_brief_notes.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/content_brief_notes/{id} - Aktualisiert eine Briefing-Notiz. REST-Parameter: content_brief_note_id (required, integer). note_type, content, order (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_note_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Content Brief Note (ERFORDERLICH).'
                ],
                'note_type' => [
                    'type' => 'string',
                    'enum' => ['instruction', 'source', 'constraint', 'example', 'avoid'],
                    'description' => 'Typ der Notiz: instruction, source, constraint, example, avoid.'
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Freitext-Inhalt der Notiz.'
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Sortierung / Position.'
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Content Brief Note nicht bearbeiten (Policy).');
            }

            // Validate note_type enum if provided
            if (isset($arguments['note_type']) && !array_key_exists($arguments['note_type'], BrandsContentBriefNote::NOTE_TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger note_type. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefNote::NOTE_TYPES)));
            }

            $updateData = [];

            foreach (['note_type', 'content', 'order'] as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $note->update($updateData);
            }

            $note->refresh();
            $note->load('contentBrief');

            return ToolResult::success([
                'id' => $note->id,
                'content_brief_id' => $note->content_brief_id,
                'content_brief_name' => $note->contentBrief->name,
                'note_type' => $note->note_type,
                'note_type_label' => BrandsContentBriefNote::NOTE_TYPES[$note->note_type] ?? $note->note_type,
                'content' => $note->content,
                'order' => $note->order,
                'updated_at' => $note->updated_at->toIso8601String(),
                'message' => "Note ('{$note->note_type}') erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Content Brief Note: ' . $e->getMessage());
        }
    }
}
