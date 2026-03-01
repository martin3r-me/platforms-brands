<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefLink;
use Illuminate\Support\Facades\Gate;

class GetTopicClusterMapTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_topic_cluster_map.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/{brand_id}/topic_cluster_map - Liefert die Topic-Cluster-Struktur einer Marke als Graph-Daten. Enthält Nodes (Content Briefs) und Edges (Links) für die Visualisierung der internen Verlinkungsarchitektur.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Marke.'
                ],
            ],
            'required' => ['brand_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $brandId = $arguments['brand_id'] ?? null;
            if (!$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'brand_id ist erforderlich.');
            }

            $brand = BrandsBrand::find($brandId);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Marke wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $brand)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Marke.');
            }

            // Get all content briefs for this brand
            $boards = BrandsContentBriefBoard::where('brand_id', $brandId)
                ->orderBy('order')
                ->get();

            $boardIds = $boards->pluck('id')->toArray();

            // Get all links between these boards
            $links = BrandsContentBriefLink::whereIn('source_content_brief_id', $boardIds)
                ->whereIn('target_content_brief_id', $boardIds)
                ->get();

            // Build nodes
            $nodes = $boards->map(function ($board) use ($links) {
                $outgoingCount = $links->where('source_content_brief_id', $board->id)->count();
                $incomingCount = $links->where('target_content_brief_id', $board->id)->count();

                return [
                    'id' => $board->id,
                    'uuid' => $board->uuid,
                    'name' => $board->name,
                    'content_type' => $board->content_type,
                    'content_type_label' => BrandsContentBriefBoard::CONTENT_TYPES[$board->content_type] ?? $board->content_type,
                    'status' => $board->status,
                    'status_label' => BrandsContentBriefBoard::STATUSES[$board->status] ?? $board->status,
                    'target_slug' => $board->target_slug,
                    'outgoing_links_count' => $outgoingCount,
                    'incoming_links_count' => $incomingCount,
                ];
            })->values()->toArray();

            // Build edges
            $edges = $links->map(function ($link) {
                return [
                    'id' => $link->id,
                    'source' => $link->source_content_brief_id,
                    'target' => $link->target_content_brief_id,
                    'link_type' => $link->link_type,
                    'link_type_label' => BrandsContentBriefLink::LINK_TYPES[$link->link_type] ?? $link->link_type,
                    'anchor_hint' => $link->anchor_hint,
                ];
            })->values()->toArray();

            // Identify pillar articles (content_type = pillar)
            $pillars = $boards->where('content_type', 'pillar')->values();
            $clusters = [];

            foreach ($pillars as $pillar) {
                $clusterBoardIds = $links
                    ->where('source_content_brief_id', $pillar->id)
                    ->where('link_type', 'pillar_to_cluster')
                    ->pluck('target_content_brief_id')
                    ->toArray();

                $clusters[] = [
                    'pillar_id' => $pillar->id,
                    'pillar_name' => $pillar->name,
                    'pillar_status' => $pillar->status,
                    'cluster_brief_ids' => $clusterBoardIds,
                    'cluster_count' => count($clusterBoardIds),
                ];
            }

            return ToolResult::success([
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'nodes' => $nodes,
                'edges' => $edges,
                'clusters' => $clusters,
                'stats' => [
                    'total_nodes' => count($nodes),
                    'total_edges' => count($edges),
                    'total_pillars' => count($clusters),
                ],
                'message' => count($nodes) . ' Content Briefs und ' . count($edges) . ' Links in der Topic-Cluster-Map für "' . $brand->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Topic-Cluster-Map: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_brief', 'topic_cluster', 'visualization'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
