<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Platform\Core\Models\User;
use Illuminate\Support\Collection;

class SeoKeywordService
{
    public function __construct(
        protected DataForSeoClientService $dataForSeoClient,
        protected SeoBudgetGuardService $budgetGuard,
    ) {}

    public function addKeyword(BrandsSeoBoard $board, array $data, ?User $user = null): BrandsSeoKeyword
    {
        return BrandsSeoKeyword::create([
            'seo_board_id' => $board->id,
            'keyword_cluster_id' => $data['keyword_cluster_id'] ?? null,
            'keyword' => $data['keyword'],
            'search_volume' => $data['search_volume'] ?? null,
            'keyword_difficulty' => $data['keyword_difficulty'] ?? null,
            'cpc_cents' => $data['cpc_cents'] ?? null,
            'trend' => $data['trend'] ?? null,
            'search_intent' => $data['search_intent'] ?? null,
            'keyword_type' => $data['keyword_type'] ?? null,
            'content_idea' => $data['content_idea'] ?? null,
            'priority' => $data['priority'] ?? null,
            'url' => $data['url'] ?? null,
            'position' => $data['position'] ?? null,
            'notes' => $data['notes'] ?? null,
            'user_id' => $user?->id,
            'team_id' => $board->team_id,
        ]);
    }

    public function addKeywords(BrandsSeoBoard $board, array $keywordsData, ?User $user = null): Collection
    {
        $keywords = collect();

        foreach ($keywordsData as $data) {
            $keywords->push($this->addKeyword($board, $data, $user));
        }

        return $keywords;
    }

    public function updateKeyword(BrandsSeoKeyword $keyword, array $data): BrandsSeoKeyword
    {
        $updateData = [];

        foreach ([
            'keyword', 'keyword_cluster_id', 'search_volume', 'keyword_difficulty',
            'cpc_cents', 'trend', 'search_intent', 'keyword_type', 'content_idea',
            'priority', 'url', 'position', 'notes',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $keyword->update($updateData);
        }

        return $keyword->fresh();
    }

    public function moveToCluster(BrandsSeoKeyword $keyword, ?int $clusterId): BrandsSeoKeyword
    {
        $keyword->update(['keyword_cluster_id' => $clusterId]);
        return $keyword->fresh();
    }

    public function deleteKeyword(BrandsSeoKeyword $keyword): void
    {
        $keyword->delete();
    }

    public function createCluster(BrandsSeoBoard $board, array $data, ?User $user = null): BrandsSeoKeywordCluster
    {
        return BrandsSeoKeywordCluster::create([
            'seo_board_id' => $board->id,
            'name' => $data['name'] ?? 'Neuer Cluster',
            'color' => $data['color'] ?? null,
            'user_id' => $user?->id,
            'team_id' => $board->team_id,
        ]);
    }

    public function updateCluster(BrandsSeoKeywordCluster $cluster, array $data): BrandsSeoKeywordCluster
    {
        $updateData = [];

        foreach (['name', 'color'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $cluster->update($updateData);
        }

        return $cluster->fresh();
    }

    public function deleteCluster(BrandsSeoKeywordCluster $cluster): void
    {
        // Keywords im Cluster werden auf null gesetzt (nullOnDelete FK)
        $cluster->delete();
    }

    /**
     * Metriken für Keywords über DataForSEO abrufen.
     */
    public function fetchMetrics(BrandsSeoBoard $board, ?Collection $keywords = null, ?User $user = null): array
    {
        $keywords = $keywords ?? $board->keywords;

        if ($keywords->isEmpty()) {
            return ['fetched' => 0, 'cost_cents' => 0];
        }

        $keywordTexts = $keywords->pluck('keyword')->toArray();
        $estimatedCost = $this->dataForSeoClient->estimateCost(count($keywordTexts));

        if (!$this->budgetGuard->canFetch($board, $estimatedCost)) {
            return ['fetched' => 0, 'cost_cents' => 0, 'error' => 'Budget limit exceeded'];
        }

        $metrics = $this->dataForSeoClient->fetchKeywordMetrics($keywordTexts);

        if (empty($metrics)) {
            return ['fetched' => 0, 'cost_cents' => 0];
        }

        $fetchedCount = 0;
        foreach ($keywords as $keyword) {
            if (isset($metrics[$keyword->keyword])) {
                $m = $metrics[$keyword->keyword];
                $keyword->update([
                    'search_volume' => $m['search_volume'] ?? $keyword->search_volume,
                    'keyword_difficulty' => $m['keyword_difficulty'] ?? $keyword->keyword_difficulty,
                    'cpc_cents' => $m['cpc'] ?? $keyword->cpc_cents,
                    'last_fetched_at' => now(),
                    'dataforseo_raw' => $m,
                ]);
                $fetchedCount++;
            }
        }

        $actualCost = $this->dataForSeoClient->estimateCost($fetchedCount);
        $this->budgetGuard->recordCost($board, 'fetch_metrics', $fetchedCount, $actualCost, $user);

        $board->update(['last_refreshed_at' => now()]);

        return ['fetched' => $fetchedCount, 'cost_cents' => $actualCost];
    }
}
