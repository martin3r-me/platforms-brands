<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBriefSection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateContentBriefSectionTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_brief_sections.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/content_brief_sections/{id} - Aktualisiert einen Gliederungsabschnitt (Outline Section). REST-Parameter: content_brief_section_id (required, integer). heading, heading_level, description, target_keywords, notes, order (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_section_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Content Brief Section (ERFORDERLICH).'
                ],
                'heading' => [
                    'type' => 'string',
                    'description' => 'Vorgeschlagene Überschrift.'
                ],
                'heading_level' => [
                    'type' => 'string',
                    'enum' => ['h2', 'h3', 'h4'],
                    'description' => 'Heading-Level: h2, h3 oder h4.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Kurze Inhaltsbeschreibung.'
                ],
                'target_keywords' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Ziel-Keywords für diesen Abschnitt.'
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Hinweise für Texter/LLM.'
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Sortierung / Position in der Gliederung.'
                ],
            ],
            'required' => ['content_brief_section_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'content_brief_section_id', BrandsContentBriefSection::class,
                'CONTENT_BRIEF_SECTION_NOT_FOUND', 'Die angegebene Content Brief Section wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $section = $validation['model'];
            $section->load('contentBrief');

            // Authorization: user must be able to update the parent content brief
            try {
                Gate::forUser($context->user)->authorize('update', $section->contentBrief);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Content Brief Section nicht bearbeiten (Policy).');
            }

            // Validate heading_level enum if provided
            if (isset($arguments['heading_level']) && !array_key_exists($arguments['heading_level'], BrandsContentBriefSection::HEADING_LEVELS)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger heading_level. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefSection::HEADING_LEVELS)));
            }

            $updateData = [];

            foreach (['heading', 'heading_level', 'description', 'notes', 'order'] as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            // target_keywords needs special handling (can be set to empty array)
            if (array_key_exists('target_keywords', $arguments)) {
                $updateData['target_keywords'] = $arguments['target_keywords'];
            }

            if (!empty($updateData)) {
                $section->update($updateData);
            }

            $section->refresh();
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
                'updated_at' => $section->updated_at->toIso8601String(),
                'message' => "Section '{$section->heading}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Content Brief Section: ' . $e->getMessage());
        }
    }
}
