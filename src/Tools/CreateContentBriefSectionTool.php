<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefSection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateContentBriefSectionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_sections.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_brief_sections - Erstellt einen neuen Gliederungsabschnitt (Outline Section) innerhalb eines Content Briefs. REST-Parameter: content_brief_id (required, integer) - Content Brief Board-ID. heading (required, string) - Vorgeschlagene Überschrift. heading_level (optional, string) - h2|h3|h4 (Standard: h2). description (optional, string) - Kurze Inhaltsbeschreibung. target_keywords (optional, array) - Ziel-Keywords für diesen Abschnitt. notes (optional, string) - Hinweise für Texter/LLM. order (optional, integer) - Sortierung.';
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
                'heading' => [
                    'type' => 'string',
                    'description' => 'Vorgeschlagene Überschrift für diesen Abschnitt (ERFORDERLICH).'
                ],
                'heading_level' => [
                    'type' => 'string',
                    'enum' => ['h2', 'h3', 'h4'],
                    'description' => 'Heading-Level: h2, h3 oder h4. Standard: h2.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Kurze Inhaltsbeschreibung: was soll dieser Abschnitt leisten?'
                ],
                'target_keywords' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Welche Keywords soll dieser Abschnitt bedienen?'
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optionale Hinweise für Texter/LLM.'
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Sortierung. Wenn nicht angegeben, wird automatisch ans Ende sortiert.'
                ],
            ],
            'required' => ['content_brief_id', 'heading']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $contentBriefId = $arguments['content_brief_id'] ?? null;
            $heading = $arguments['heading'] ?? null;

            if (!$contentBriefId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_brief_id ist erforderlich.');
            }
            if (!$heading) {
                return ToolResult::error('VALIDATION_ERROR', 'heading ist erforderlich.');
            }

            // Validate heading_level enum
            $headingLevel = $arguments['heading_level'] ?? 'h2';
            if (!array_key_exists($headingLevel, BrandsContentBriefSection::HEADING_LEVELS)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger heading_level. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefSection::HEADING_LEVELS)));
            }

            // Check content brief exists
            $board = BrandsContentBriefBoard::find($contentBriefId);
            if (!$board) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief Board wurde nicht gefunden.');
            }

            // Authorization: user must be able to update the content brief
            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Sections für dieses Content Brief erstellen (Policy).');
            }

            // Auto-calculate order if not provided
            $order = $arguments['order'] ?? null;
            if ($order === null) {
                $maxOrder = BrandsContentBriefSection::where('content_brief_id', $contentBriefId)->max('order') ?? 0;
                $order = $maxOrder + 1;
            }

            $section = BrandsContentBriefSection::create([
                'content_brief_id' => $contentBriefId,
                'order' => $order,
                'heading' => $heading,
                'heading_level' => $headingLevel,
                'description' => $arguments['description'] ?? null,
                'target_keywords' => $arguments['target_keywords'] ?? null,
                'notes' => $arguments['notes'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $board->team_id,
            ]);

            $section->load('contentBrief');

            return ToolResult::success([
                'id' => $section->id,
                'content_brief_id' => $section->content_brief_id,
                'content_brief_name' => $section->contentBrief->name,
                'order' => $section->order,
                'heading' => $section->heading,
                'heading_level' => $section->heading_level,
                'heading_level_label' => BrandsContentBriefSection::HEADING_LEVELS[$section->heading_level] ?? $section->heading_level,
                'description' => $section->description,
                'target_keywords' => $section->target_keywords,
                'notes' => $section->notes,
                'created_at' => $section->created_at->toIso8601String(),
                'message' => "Section '{$section->heading}' erfolgreich für Content Brief '{$section->contentBrief->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Content Brief Section: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_brief_section', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
