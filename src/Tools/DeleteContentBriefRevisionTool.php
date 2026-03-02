<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefRevision;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteContentBriefRevisionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_revisions.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/content_brief_revisions/{id} - Löscht eine Revision. Parameter: revision_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'revision_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Revision.',
                ],
            ],
            'required' => ['revision_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $revisionId = $arguments['revision_id'] ?? null;
            if (!$revisionId) {
                return ToolResult::error('VALIDATION_ERROR', 'revision_id ist erforderlich.');
            }

            $revision = BrandsContentBriefRevision::with('contentBriefBoard')->find($revisionId);
            if (!$revision) {
                return ToolResult::error('NOT_FOUND', 'Revision nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $revision->contentBriefBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Keine Berechtigung.');
            }

            $summary = $revision->summary;
            $revision->delete();

            return ToolResult::success([
                'deleted' => true,
                'message' => "Revision gelöscht: {$summary}",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Revision: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_brief', 'revision', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
        ];
    }
}
