<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBriefSection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteContentBriefSectionTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_brief_sections.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/content_brief_sections/{id} - Löscht einen Gliederungsabschnitt (Outline Section). REST-Parameter: content_brief_section_id (required, integer) - Section-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_section_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Content Brief Section (ERFORDERLICH).'
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Content Brief Section nicht löschen (Policy).');
            }

            $sectionId = $section->id;
            $heading = $section->heading;
            $contentBriefName = $section->contentBrief->name;

            $section->delete();

            return ToolResult::success([
                'content_brief_section_id' => $sectionId,
                'heading' => $heading,
                'content_brief_name' => $contentBriefName,
                'message' => "Section '{$heading}' aus Content Brief '{$contentBriefName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Content Brief Section: ' . $e->getMessage());
        }
    }
}
