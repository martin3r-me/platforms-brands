<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefKeywordCluster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Entfernen einer Keyword-Cluster-Verknüpfung von einem Content Brief.
 */
class DeleteContentBriefKeywordClusterTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_keyword_clusters.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/content_brief_keyword_clusters/{id} - Entfernt die Verknüpfung eines Keyword Clusters von einem Content Brief. '
            . 'REST-Parameter: content_brief_id (required, integer), keyword_cluster_link_id (required, integer – ID der Verknüpfung).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Briefs (ERFORDERLICH).',
                ],
                'keyword_cluster_link_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Keyword-Cluster-Verknüpfung (ERFORDERLICH). Nutze "brands.content_brief_keyword_clusters.GET" um Verknüpfungs-IDs zu sehen.',
                ],
            ],
            'required' => ['content_brief_id', 'keyword_cluster_link_id'],
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

            $linkId = $arguments['keyword_cluster_link_id'] ?? null;
            if (!$linkId) {
                return ToolResult::error('VALIDATION_ERROR', 'keyword_cluster_link_id ist erforderlich.');
            }

            $contentBrief = BrandsContentBriefBoard::find($contentBriefId);
            if (!$contentBrief) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief wurde nicht gefunden.');
            }

            // Policy: update auf Content Brief prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $contentBrief);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Keyword-Cluster-Verknüpfungen für dieses Content Brief entfernen (Policy).');
            }

            $link = BrandsContentBriefKeywordCluster::where('id', $linkId)
                ->where('content_brief_id', $contentBriefId)
                ->with('keywordCluster')
                ->first();

            if (!$link) {
                return ToolResult::error('LINK_NOT_FOUND', 'Die angegebene Keyword-Cluster-Verknüpfung wurde nicht gefunden oder gehört nicht zu diesem Content Brief.');
            }

            $clusterName = $link->keywordCluster->name;
            $clusterId = $link->seo_keyword_cluster_id;
            $role = $link->role;

            $link->delete();

            return ToolResult::success([
                'content_brief_id' => $contentBriefId,
                'deleted_link_id' => $linkId,
                'seo_keyword_cluster_id' => $clusterId,
                'cluster_name' => $clusterName,
                'role' => $role,
                'message' => "Keyword-Cluster-Verknüpfung entfernt: '{$clusterName}' ({$role}) ist nicht mehr mit dem Content Brief verknüpft.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Entfernen der Keyword-Cluster-Verknüpfung: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_brief', 'keyword_cluster', 'seo', 'link', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
            'side_effects' => ['deletes'],
        ];
    }
}
