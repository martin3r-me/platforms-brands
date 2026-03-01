<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBriefLink;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteContentBriefLinkTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_brief_links.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/content_brief_links/{id} - Löscht eine Verlinkung zwischen Content Briefs. REST-Parameter: content_brief_link_id (required, integer) - Link-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_link_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Content Brief Links (ERFORDERLICH).'
                ],
            ],
            'required' => ['content_brief_link_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'content_brief_link_id', BrandsContentBriefLink::class,
                'CONTENT_BRIEF_LINK_NOT_FOUND', 'Der angegebene Content Brief Link wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $link = $validation['model'];
            $link->load(['sourceContentBrief', 'targetContentBrief']);

            // Authorization: user must be able to update the source content brief
            try {
                Gate::forUser($context->user)->authorize('update', $link->sourceContentBrief);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Content Brief Link nicht löschen (Policy).');
            }

            $linkId = $link->id;
            $sourceName = $link->sourceContentBrief->name;
            $targetName = $link->targetContentBrief->name;
            $linkType = $link->link_type;

            $link->delete();

            return ToolResult::success([
                'content_brief_link_id' => $linkId,
                'source_content_brief_name' => $sourceName,
                'target_content_brief_name' => $targetName,
                'link_type' => $linkType,
                'message' => "Link '{$sourceName}' → '{$targetName}' ({$linkType}) wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Content Brief Links: ' . $e->getMessage());
        }
    }
}
