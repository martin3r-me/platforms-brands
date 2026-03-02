<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefRevision;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetContentBriefRevisionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_revision.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_brief_revisions/{id} - Ruft eine einzelne Revision mit allen Details ab (inkl. metrics_before, metrics_after, changes Array).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'revision_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Revision (ERFORDERLICH).',
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

            $revision = BrandsContentBriefRevision::with(['contentBriefBoard.brand', 'user'])->find($revisionId);
            if (!$revision) {
                return ToolResult::error('NOT_FOUND', 'Revision nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $revision->contentBriefBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff auf dieses Content Brief Board.');
            }

            return ToolResult::success([
                'id' => $revision->id,
                'uuid' => $revision->uuid,
                'content_brief_board_id' => $revision->content_brief_board_id,
                'brief_name' => $revision->contentBriefBoard->name,
                'brand_name' => $revision->contentBriefBoard->brand->name,
                'revision_type' => $revision->revision_type,
                'summary' => $revision->summary,
                'metrics_before' => $revision->metrics_before,
                'metrics_after' => $revision->metrics_after,
                'metrics_delta' => $revision->metrics_delta,
                'changes' => $revision->changes,
                'user_name' => $revision->user?->name,
                'revised_at' => $revision->revised_at->toIso8601String(),
                'created_at' => $revision->created_at->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Revision: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_brief', 'revision', 'get', 'detail'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
