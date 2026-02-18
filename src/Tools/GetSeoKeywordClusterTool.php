<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetSeoKeywordClusterTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_cluster.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/seo_keyword_clusters/{id} - Ruft einen einzelnen Keyword-Cluster mit Keywords ab. REST-Parameter: id (required, integer) - Cluster-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Keyword-Clusters.'
                ]
            ],
            'required' => ['id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Keyword-Cluster-ID ist erforderlich.');
            }

            $cluster = BrandsSeoKeywordCluster::with(['seoBoard', 'keywords', 'user', 'team'])
                ->find($arguments['id']);

            if (!$cluster) {
                return ToolResult::error('CLUSTER_NOT_FOUND', 'Der angegebene Keyword-Cluster wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $cluster->seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Keyword-Cluster (Policy).');
            }

            $keywords = $cluster->keywords->map(function ($kw) {
                return [
                    'id' => $kw->id,
                    'keyword' => $kw->keyword,
                    'search_volume' => $kw->search_volume,
                    'keyword_difficulty' => $kw->keyword_difficulty,
                    'cpc_cents' => $kw->cpc_cents,
                    'search_intent' => $kw->search_intent,
                    'priority' => $kw->priority,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'id' => $cluster->id,
                'uuid' => $cluster->uuid,
                'name' => $cluster->name,
                'color' => $cluster->color,
                'order' => $cluster->order,
                'seo_board_id' => $cluster->seo_board_id,
                'seo_board_name' => $cluster->seoBoard->name,
                'keywords' => $keywords,
                'keywords_count' => count($keywords),
                'created_at' => $cluster->created_at->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Keyword-Clusters: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'seo_keyword_cluster', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
