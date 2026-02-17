<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Platform\Brands\Models\BrandsGuidelineEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateGuidelineEntryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.guideline_entries.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/guideline_chapters/{chapter_id}/entries - Erstellt einen neuen Guideline-Eintrag (Regel mit Do/Don\'t). REST-Parameter: chapter_id (required), title (required), rule_text (required), rationale (optional), do_example (optional), dont_example (optional), cross_references (optional, array von {board_type, board_id, label}).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'chapter_id' => ['type' => 'integer', 'description' => 'ID des Kapitels (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Titel der Regel (ERFORDERLICH).'],
                'rule_text' => ['type' => 'string', 'description' => 'Der eigentliche Regel-Text (ERFORDERLICH).'],
                'rationale' => ['type' => 'string', 'description' => 'Begründung warum diese Regel existiert.'],
                'do_example' => ['type' => 'string', 'description' => 'Positives Beispiel (Do): Wie es richtig gemacht wird.'],
                'dont_example' => ['type' => 'string', 'description' => 'Negatives Beispiel (Don\'t): Wie es NICHT gemacht werden soll.'],
                'cross_references' => [
                    'type' => 'array',
                    'description' => 'Cross-Referenzen zu anderen Boards. Array von Objekten mit board_type (z.B. "ci-board", "logo-board"), board_id, label.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'board_type' => ['type' => 'string'],
                            'board_id' => ['type' => 'integer'],
                            'label' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'required' => ['chapter_id', 'title', 'rule_text']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $chapterId = $arguments['chapter_id'] ?? null;
            if (!$chapterId) {
                return ToolResult::error('VALIDATION_ERROR', 'chapter_id ist erforderlich.');
            }

            $chapter = BrandsGuidelineChapter::find($chapterId);
            if (!$chapter) {
                return ToolResult::error('CHAPTER_NOT_FOUND', 'Das angegebene Kapitel wurde nicht gefunden.');
            }

            $board = $chapter->guidelineBoard;
            if (!$board) {
                return ToolResult::error('BOARD_NOT_FOUND', 'Das zugehörige Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Einträge für dieses Board erstellen.');
            }

            $title = $arguments['title'] ?? null;
            if (!$title) {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            $ruleText = $arguments['rule_text'] ?? null;
            if (!$ruleText) {
                return ToolResult::error('VALIDATION_ERROR', 'rule_text ist erforderlich.');
            }

            $entry = BrandsGuidelineEntry::create([
                'guideline_chapter_id' => $chapter->id,
                'title' => $title,
                'rule_text' => $ruleText,
                'rationale' => $arguments['rationale'] ?? null,
                'do_example' => $arguments['do_example'] ?? null,
                'dont_example' => $arguments['dont_example'] ?? null,
                'cross_references' => $arguments['cross_references'] ?? null,
            ]);

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
                'created_at' => $entry->created_at->toIso8601String(),
                'message' => "Guideline-Eintrag '{$entry->title}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Guideline-Eintrags: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'guideline_entry', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
