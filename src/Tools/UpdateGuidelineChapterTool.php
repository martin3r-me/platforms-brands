<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateGuidelineChapterTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.guideline_chapters.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/guideline_chapters/{id} - Aktualisiert ein Kapitel. REST-Parameter: chapter_id (required), title, description, icon (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'chapter_id' => ['type' => 'integer', 'description' => 'ID des Kapitels (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Optional: Titel des Kapitels.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                'icon' => ['type' => 'string', 'description' => 'Optional: Heroicon-Name.'],
            ],
            'required' => ['chapter_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'chapter_id',
                BrandsGuidelineChapter::class, 'CHAPTER_NOT_FOUND',
                'Das angegebene Kapitel wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $chapter = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $chapter);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Kapitel nicht bearbeiten.');
            }

            $fields = ['title', 'description', 'icon'];
            $updateData = [];
            foreach ($fields as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $chapter->update($updateData);
            }

            $chapter->refresh();
            $chapter->load('guidelineBoard');

            return ToolResult::success([
                'id' => $chapter->id,
                'uuid' => $chapter->uuid,
                'title' => $chapter->title,
                'description' => $chapter->description,
                'icon' => $chapter->icon,
                'order' => $chapter->order,
                'guideline_board_id' => $chapter->guideline_board_id,
                'updated_at' => $chapter->updated_at->toIso8601String(),
                'message' => "Kapitel '{$chapter->title}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Kapitels: ' . $e->getMessage());
        }
    }
}
