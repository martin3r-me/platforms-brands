<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsGuidelineEntry;
use Illuminate\Support\Facades\Gate;

class GetGuidelineEntryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.guideline_entry.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/guideline_entries/{id} - Gibt einen einzelnen Guideline-Eintrag zurück. REST-Parameter: entry_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entry_id' => ['type' => 'integer', 'description' => 'ID des Guideline-Eintrags (ERFORDERLICH).'],
            ],
            'required' => ['entry_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $entryId = $arguments['entry_id'] ?? null;
            if (!$entryId) {
                return ToolResult::error('VALIDATION_ERROR', 'entry_id ist erforderlich.');
            }

            $entry = BrandsGuidelineEntry::with('guidelineChapter.guidelineBoard')->find($entryId);
            if (!$entry) {
                return ToolResult::error('ENTRY_NOT_FOUND', 'Der angegebene Guideline-Eintrag wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $entry)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Eintrag.');
            }

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'title' => $entry->title,
                'rule_text' => $entry->rule_text,
                'rationale' => $entry->rationale,
                'do_example' => $entry->do_example,
                'dont_example' => $entry->dont_example,
                'do_image_path' => $entry->do_image_path,
                'dont_image_path' => $entry->dont_image_path,
                'cross_references' => $entry->cross_references,
                'order' => $entry->order,
                'guideline_chapter_id' => $entry->guideline_chapter_id,
                'chapter_title' => $entry->guidelineChapter->title,
                'created_at' => $entry->created_at->toIso8601String(),
                'updated_at' => $entry->updated_at->toIso8601String(),
                'message' => "Guideline-Eintrag '{$entry->title}' geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Guideline-Eintrags: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'guideline_entry', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
