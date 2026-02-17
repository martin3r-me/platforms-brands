<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsGuidelineEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateGuidelineEntryTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.guideline_entries.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/guideline_entries/{id} - Aktualisiert einen Guideline-Eintrag. REST-Parameter: entry_id (required), title, rule_text, rationale, do_example, dont_example, cross_references (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entry_id' => ['type' => 'integer', 'description' => 'ID des Guideline-Eintrags (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Optional: Titel der Regel.'],
                'rule_text' => ['type' => 'string', 'description' => 'Optional: Regel-Text.'],
                'rationale' => ['type' => 'string', 'description' => 'Optional: Begründung.'],
                'do_example' => ['type' => 'string', 'description' => 'Optional: Positives Beispiel.'],
                'dont_example' => ['type' => 'string', 'description' => 'Optional: Negatives Beispiel.'],
                'cross_references' => [
                    'type' => 'array',
                    'description' => 'Optional: Cross-Referenzen.',
                    'items' => ['type' => 'object'],
                ],
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
                Gate::forUser($context->user)->authorize('update', $entry);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Guideline-Eintrag nicht bearbeiten.');
            }

            $fields = ['title', 'rule_text', 'rationale', 'do_example', 'dont_example', 'cross_references'];
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
            $entry->load('guidelineChapter');

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'title' => $entry->title,
                'rule_text' => $entry->rule_text,
                'rationale' => $entry->rationale,
                'do_example' => $entry->do_example,
                'dont_example' => $entry->dont_example,
                'cross_references' => $entry->cross_references,
                'order' => $entry->order,
                'guideline_chapter_id' => $entry->guideline_chapter_id,
                'updated_at' => $entry->updated_at->toIso8601String(),
                'message' => "Guideline-Eintrag '{$entry->title}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Guideline-Eintrags: ' . $e->getMessage());
        }
    }
}
