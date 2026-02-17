<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Platform\Brands\Models\BrandsGuidelineEntry;
use Illuminate\Support\Facades\Gate;

class ListGuidelineEntriesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.guideline_entries.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/guideline_chapters/{chapter_id}/entries - Listet Guideline-Einträge eines Kapitels auf. REST-Parameter: chapter_id (required, integer).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'chapter_id' => ['type' => 'integer', 'description' => 'REST-Parameter (required): ID des Kapitels.'],
                ]
            ]
        );
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

            $chapter = BrandsGuidelineChapter::with('guidelineBoard')->find($chapterId);
            if (!$chapter) {
                return ToolResult::error('CHAPTER_NOT_FOUND', 'Das angegebene Kapitel wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $chapter->guidelineBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Board.');
            }

            $query = BrandsGuidelineEntry::query()
                ->where('guideline_chapter_id', $chapterId)
                ->with('guidelineChapter');

            $this->applyStandardFilters($query, $arguments, ['title', 'created_at', 'updated_at']);
            $this->applyStandardSearch($query, $arguments, ['title', 'rule_text', 'rationale']);
            $this->applyStandardSort($query, $arguments, ['title', 'order', 'created_at', 'updated_at'], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $entries = $query->get();

            $entriesList = $entries->map(function ($entry) {
                return [
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
                    'updated_at' => $entry->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'entries' => $entriesList,
                'count' => count($entriesList),
                'chapter_id' => $chapterId,
                'chapter_title' => $chapter->title,
                'message' => count($entriesList) > 0
                    ? count($entriesList) . ' Guideline-Einträge gefunden für Kapitel "' . $chapter->title . '".'
                    : 'Keine Guideline-Einträge gefunden für Kapitel "' . $chapter->title . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Guideline-Einträge: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'guideline_entry', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
