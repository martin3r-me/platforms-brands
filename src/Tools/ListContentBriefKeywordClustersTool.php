<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefKeywordCluster;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten der verknüpften Keyword Cluster eines Content Briefs.
 *
 * Zeigt alle verknüpften SEO Keyword Cluster inkl. Keywords und Metriken.
 */
class ListContentBriefKeywordClustersTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_keyword_clusters.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_brief_keyword_clusters - Listet alle verknüpften SEO Keyword Cluster eines Content Briefs auf. '
            . 'Enthält Keywords und Metriken (Search Volume, Keyword Difficulty, CPC) pro Cluster. '
            . 'REST-Parameter: content_brief_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Briefs (ERFORDERLICH). Nutze "brands.content_brief_boards.GET" um IDs zu sehen.',
                ],
            ],
            'required' => ['content_brief_id'],
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

            $contentBrief = BrandsContentBriefBoard::with('brand')->find($contentBriefId);
            if (!$contentBrief) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief wurde nicht gefunden.');
            }

            // Policy: view auf Content Brief prüfen
            if (!Gate::forUser($context->user)->allows('view', $contentBrief)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Content Brief.');
            }

            $links = BrandsContentBriefKeywordCluster::query()
                ->where('content_brief_id', $contentBriefId)
                ->with(['keywordCluster.keywords', 'keywordCluster.seoBoard'])
                ->orderByRaw("CASE WHEN role = 'primary' THEN 0 WHEN role = 'secondary' THEN 1 ELSE 2 END")
                ->orderBy('created_at', 'asc')
                ->get();

            $clustersList = $links->map(function ($link) {
                $cluster = $link->keywordCluster;
                $keywords = $cluster->keywords->map(function ($kw) {
                    return [
                        'id' => $kw->id,
                        'keyword' => $kw->keyword,
                        'search_volume' => $kw->search_volume,
                        'keyword_difficulty' => $kw->keyword_difficulty,
                        'cpc_cents' => $kw->cpc_cents,
                        'search_intent' => $kw->search_intent,
                        'position' => $kw->position,
                    ];
                })->values()->toArray();

                $totalSearchVolume = $cluster->keywords->sum('search_volume');
                $avgDifficulty = $cluster->keywords->avg('keyword_difficulty');

                return [
                    'id' => $link->id,
                    'seo_keyword_cluster_id' => $link->seo_keyword_cluster_id,
                    'cluster_name' => $cluster->name,
                    'cluster_color' => $cluster->color,
                    'seo_board_id' => $cluster->seo_board_id,
                    'seo_board_name' => $cluster->seoBoard?->name,
                    'role' => $link->role,
                    'role_label' => BrandsContentBriefKeywordCluster::ROLES[$link->role] ?? $link->role,
                    'keywords' => $keywords,
                    'keyword_count' => count($keywords),
                    'total_search_volume' => $totalSearchVolume,
                    'avg_keyword_difficulty' => $avgDifficulty ? round($avgDifficulty, 1) : null,
                    'created_at' => $link->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'content_brief_id' => $contentBrief->id,
                'content_brief_name' => $contentBrief->name,
                'clusters' => $clustersList,
                'count' => count($clustersList),
                'message' => count($clustersList) > 0
                    ? count($clustersList) . " Keyword Cluster für Content Brief '{$contentBrief->name}' gefunden."
                    : "Keine Keyword Cluster für Content Brief '{$contentBrief->name}' verknüpft.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Keyword Cluster: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_brief', 'keyword_cluster', 'seo', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
