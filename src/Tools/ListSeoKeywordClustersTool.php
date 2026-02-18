<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Illuminate\Support\Facades\Gate;

class ListSeoKeywordClustersTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_clusters.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/seo_boards/{seo_board_id}/keyword_clusters - Listet Keyword-Cluster eines SEO Boards auf. REST-Parameter: seo_board_id (required, integer) - SEO Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des SEO Boards. Nutze "brands.seo_boards.GET" um SEO Boards zu finden.'
                ],
            ],
            'required' => ['seo_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $seoBoardId = $arguments['seo_board_id'] ?? null;
            if (!$seoBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'seo_board_id ist erforderlich.');
            }

            $seoBoard = BrandsSeoBoard::find($seoBoardId);
            if (!$seoBoard) {
                return ToolResult::error('SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $seoBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses SEO Board.');
            }

            $clusters = $seoBoard->keywordClusters()
                ->with(['keywords'])
                ->orderBy('order')
                ->get();

            $clustersList = $clusters->map(function ($cluster) {
                return [
                    'id' => $cluster->id,
                    'uuid' => $cluster->uuid,
                    'name' => $cluster->name,
                    'color' => $cluster->color,
                    'order' => $cluster->order,
                    'keywords_count' => $cluster->keywords->count(),
                    'seo_board_id' => $cluster->seo_board_id,
                    'created_at' => $cluster->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'keyword_clusters' => $clustersList,
                'count' => count($clustersList),
                'seo_board_id' => $seoBoardId,
                'seo_board_name' => $seoBoard->name,
                'message' => count($clustersList) > 0
                    ? count($clustersList) . ' Keyword-Cluster gefunden.'
                    : 'Keine Keyword-Cluster gefunden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Keyword-Cluster: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'seo_keyword_cluster', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
