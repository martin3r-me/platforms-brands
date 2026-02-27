<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoClusteringService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class AutoClusterSeoKeywordsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keywords.AUTO_CLUSTER';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/keywords/auto_cluster - Clustert ungeclusterte Keywords automatisch per SERP-URL-Overlap (Google zeigt gleiche Seiten → gleiches Thema). ACHTUNG: Kostenintensiv! Jedes Keyword = 1 SERP-Abruf (~10 Cents). 300 Keywords ≈ 30€. Budget-Limit des Boards beachten. REST-Parameter: seo_board_id (required, integer), min_overlap (optional, integer, default: 3, range: 1-10), max_keywords (optional, integer, default: 300, max: 500).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Boards (ERFORDERLICH).',
                ],
                'min_overlap' => [
                    'type' => 'integer',
                    'description' => 'Mindestanzahl gleicher URLs in den Top-10 SERP-Ergebnissen, damit zwei Keywords als verwandt gelten (Standard: 3, Bereich: 1-10). Niedrigerer Wert = größere Cluster.',
                ],
                'max_keywords' => [
                    'type' => 'integer',
                    'description' => 'Maximale Anzahl Keywords für Clustering (Standard: 300, max: 500). Sortiert nach Search Volume (wichtigste zuerst).',
                ],
            ],
            'required' => ['seo_board_id'],
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

            try {
                Gate::forUser($context->user)->authorize('update', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Keywords für dieses SEO Board clustern (Policy).');
            }

            $minOverlap = max(1, min(10, $arguments['min_overlap'] ?? 3));
            $maxKeywords = max(1, min(500, $arguments['max_keywords'] ?? 300));

            $clusteringService = app(SeoClusteringService::class);
            $result = $clusteringService->clusterBySerp($seoBoard, $context->user, $minOverlap, $maxKeywords);

            if (isset($result['error'])) {
                return ToolResult::error('BUDGET_EXCEEDED', $result['error']);
            }

            $message = $result['clusters_created'] > 0
                ? "{$result['clusters_created']} Cluster erstellt, {$result['keywords_clustered']} Keywords zugeordnet. {$result['singletons_remaining']} Keywords bleiben ungeclustert. Kosten: {$result['cost_cents']} Cents."
                : "Keine Cluster erstellt. {$result['singletons_remaining']} Keywords bleiben ungeclustert." .
                  ($result['keywords_fetched'] > 0 ? ' Tipp: min_overlap senken für größere Cluster.' : '');

            return ToolResult::success([
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                'clusters_created' => $result['clusters_created'],
                'keywords_clustered' => $result['keywords_clustered'],
                'keywords_fetched' => $result['keywords_fetched'],
                'singletons_remaining' => $result['singletons_remaining'],
                'cost_cents' => $result['cost_cents'],
                'clusters' => $result['clusters'],
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auto-Clustering: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'cluster', 'auto', 'serp', 'overlap', 'dataforseo'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['external_api', 'costs', 'creates', 'updates'],
        ];
    }
}
