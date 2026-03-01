<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefSection;

class ListContentBriefSectionsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_sections.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_brief_sections - Listet Gliederungsabschnitte (Outline Sections) eines Content Briefs auf, sortiert nach order. REST-Parameter: content_brief_id (required, integer) - Content Brief Board-ID. heading_level (optional, string) - Filtert auf ein bestimmtes Heading-Level (h2, h3, h4).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Brief Boards (ERFORDERLICH).'
                ],
                'heading_level' => [
                    'type' => 'string',
                    'enum' => ['h2', 'h3', 'h4'],
                    'description' => 'Optional: Filtert auf ein bestimmtes Heading-Level.'
                ],
            ],
            'required' => ['content_brief_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $contentBriefId = $arguments['content_brief_id'] ?? null;
            if (!$contentBriefId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_brief_id ist erforderlich.');
            }

            $board = BrandsContentBriefBoard::find($contentBriefId);
            if (!$board) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief Board wurde nicht gefunden.');
            }

            $headingLevel = $arguments['heading_level'] ?? null;
            if ($headingLevel && !array_key_exists($headingLevel, BrandsContentBriefSection::HEADING_LEVELS)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger heading_level. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefSection::HEADING_LEVELS)));
            }

            $query = BrandsContentBriefSection::where('content_brief_id', $contentBriefId);

            if ($headingLevel) {
                $query->where('heading_level', $headingLevel);
            }

            $sections = $query->orderBy('order', 'asc')->get();

            $sectionsList = $sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'content_brief_id' => $section->content_brief_id,
                    'order' => $section->order,
                    'heading' => $section->heading,
                    'heading_level' => $section->heading_level,
                    'heading_level_label' => BrandsContentBriefSection::HEADING_LEVELS[$section->heading_level] ?? $section->heading_level,
                    'description' => $section->description,
                    'target_keywords' => $section->target_keywords,
                    'notes' => $section->notes,
                    'created_at' => $section->created_at->toIso8601String(),
                    'updated_at' => $section->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'sections' => $sectionsList,
                'count' => count($sectionsList),
                'content_brief_id' => $contentBriefId,
                'content_brief_name' => $board->name,
                'message' => count($sectionsList) > 0
                    ? count($sectionsList) . ' Section(s) für Content Brief "' . $board->name . '" gefunden.'
                    : 'Keine Sections für Content Brief "' . $board->name . '" gefunden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Content Brief Sections: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_brief_section', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
