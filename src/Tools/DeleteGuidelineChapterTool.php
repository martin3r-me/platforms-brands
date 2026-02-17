<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteGuidelineChapterTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.guideline_chapters.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/guideline_chapters/{id} - Löscht ein Kapitel inkl. aller Einträge. REST-Parameter: chapter_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'chapter_id' => ['type' => 'integer', 'description' => 'ID des zu löschenden Kapitels (ERFORDERLICH).'],
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
                Gate::forUser($context->user)->authorize('delete', $chapter);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Kapitel nicht löschen.');
            }

            $chapterTitle = $chapter->title;
            $chapterId = $chapter->id;
            $boardId = $chapter->guideline_board_id;

            $chapter->delete();

            return ToolResult::success([
                'chapter_id' => $chapterId,
                'chapter_title' => $chapterTitle,
                'guideline_board_id' => $boardId,
                'message' => "Kapitel '{$chapterTitle}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Kapitels: ' . $e->getMessage());
        }
    }
}
