<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Platform\Brands\Models\BrandsSeoKeywordPosition;
use Platform\Core\Models\User;
use Platform\Integrations\Services\DataForSeoApiService;
use Illuminate\Support\Collection;

class SeoKeywordService
{
    public function __construct(
        protected DataForSeoApiService $dataForSeoApi,
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
            'content_status' => $data['content_status'] ?? 'none',
            'target_url' => $data['target_url'] ?? null,
            'published_url' => $data['published_url'] ?? null,
            'target_position' => $data['target_position'] ?? null,
            'location' => $data['location'] ?? null,
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
            'content_status', 'target_url', 'published_url', 'target_position', 'location',
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

        $user = $this->resolveUser($board, $user);
        $keywordTexts = $keywords->pluck('keyword')->toArray();
        $estimatedCost = $this->estimateCost('search_volume', count($keywordTexts));

        if (!$this->budgetGuard->canFetch($board, $estimatedCost)) {
            return ['fetched' => 0, 'cost_cents' => 0, 'error' => 'Budget limit exceeded'];
        }

        $api = $this->resolveApiService($board);
        $volumeResults = $api->getSearchVolume($user, $keywordTexts, ...array_values($this->resolveLocationLanguage($board)));

        if (empty($volumeResults)) {
            return ['fetched' => 0, 'cost_cents' => 0];
        }

        // Index nach Keyword für schnelles Lookup
        $metricsMap = [];
        foreach ($volumeResults as $result) {
            $metricsMap[$result->keyword] = $result;
        }

        $fetchedCount = 0;
        $positionSnapshots = 0;
        foreach ($keywords as $keyword) {
            if (isset($metricsMap[$keyword->keyword])) {
                $m = $metricsMap[$keyword->keyword];

                $keyword->update([
                    'search_volume' => $m->searchVolume ?? $keyword->search_volume,
                    'keyword_difficulty' => $keyword->keyword_difficulty, // Not in search_volume endpoint
                    'cpc_cents' => $m->cpcHigh !== null ? (int) round($m->cpcHigh * 100) : $keyword->cpc_cents,
                    'last_fetched_at' => now(),
                    'dataforseo_raw' => $m->toArray(),
                ]);

                $fetchedCount++;
            }
        }

        $actualCost = $this->estimateCost('search_volume', $fetchedCount);
        $this->budgetGuard->recordCost($board, 'fetch_metrics', $fetchedCount, $actualCost, $user);

        $board->update(['last_refreshed_at' => now()]);

        return ['fetched' => $fetchedCount, 'cost_cents' => $actualCost, 'position_snapshots' => $positionSnapshots];
    }

    /**
     * SERP-Rankings für alle Keywords eines Boards abrufen und Position-Snapshots erstellen.
     */
    public function fetchRankings(BrandsSeoBoard $board, ?User $user = null): array
    {
        $keywords = $board->keywords;

        if ($keywords->isEmpty()) {
            return ['fetched' => 0, 'cost_cents' => 0, 'position_snapshots' => 0];
        }

        $user = $this->resolveUser($board, $user);
        $estimatedCost = $this->estimateCost('serp', $keywords->count());

        if (!$this->budgetGuard->canFetch($board, $estimatedCost)) {
            return ['fetched' => 0, 'cost_cents' => 0, 'position_snapshots' => 0, 'error' => 'Budget limit exceeded'];
        }

        $api = $this->resolveApiService($board);
        [$locationCode, $languageCode] = array_values($this->resolveLocationLanguage($board));

        $fetchedCount = 0;
        $positionSnapshots = 0;
        $competitorEntries = [];

        foreach ($keywords as $keyword) {
            $serpResults = $api->getSerpOrganic($user, $keyword->keyword, $locationCode, $languageCode);

            if (empty($serpResults)) {
                continue;
            }

            // Eigene Position finden (falls target_url gesetzt)
            $ownPosition = null;
            $serpFeatures = [];
            foreach ($serpResults as $serpResult) {
                $serpFeatures[] = $serpResult->domain;
                if ($keyword->target_url && str_contains($serpResult->url ?? '', parse_url($keyword->target_url, PHP_URL_HOST) ?? '')) {
                    $ownPosition = $serpResult->position;
                }
            }

            if ($ownPosition !== null) {
                $lastSnapshot = BrandsSeoKeywordPosition::where('seo_keyword_id', $keyword->id)
                    ->where('search_engine', 'google')
                    ->where('device', 'desktop')
                    ->orderByDesc('tracked_at')
                    ->first();

                BrandsSeoKeywordPosition::create([
                    'seo_keyword_id' => $keyword->id,
                    'position' => $ownPosition,
                    'previous_position' => $lastSnapshot?->position,
                    'serp_features' => array_unique(array_slice($serpFeatures, 0, 10)),
                    'tracked_at' => now(),
                    'search_engine' => 'google',
                    'device' => 'desktop',
                    'location' => $keyword->location,
                ]);
                $positionSnapshots++;

                $keyword->update(['position' => $ownPosition]);
            }

            // Top-Competitor-Domains extrahieren
            foreach (array_slice($serpResults, 0, 10) as $serpResult) {
                if ($serpResult->domain) {
                    $competitorEntries[$serpResult->domain] = ($competitorEntries[$serpResult->domain] ?? 0) + 1;
                }
            }

            $fetchedCount++;
        }

        $actualCost = $this->estimateCost('serp', $fetchedCount);
        $this->budgetGuard->recordCost($board, 'fetch_rankings', $fetchedCount, $actualCost, $user);

        $board->update(['last_refreshed_at' => now()]);

        return [
            'fetched' => $fetchedCount,
            'cost_cents' => $actualCost,
            'position_snapshots' => $positionSnapshots,
            'top_competitors' => collect($competitorEntries)
                ->sortDesc()
                ->take(20)
                ->map(fn($count, $domain) => ['domain' => $domain, 'keyword_overlaps' => $count])
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Keyword-Vorschläge über Labs API abrufen.
     *
     * @return array{keywords: array, cost_cents: int}
     */
    public function discoverKeywords(BrandsSeoBoard $board, array $seedKeywords, ?User $user = null, int $limit = 100): array
    {
        if (empty($seedKeywords)) {
            return ['keywords' => [], 'cost_cents' => 0];
        }

        $user = $this->resolveUser($board, $user);
        $estimatedCost = $this->estimateCost('labs_suggestions', 1);

        if (!$this->budgetGuard->canFetch($board, $estimatedCost)) {
            return ['keywords' => [], 'cost_cents' => 0, 'error' => 'Budget limit exceeded'];
        }

        $api = $this->resolveApiService($board);
        [$locationCode, $languageCode] = array_values($this->resolveLocationLanguage($board));

        $labsResults = $api->getLabsKeywordSuggestions($user, $seedKeywords, $locationCode, $languageCode, $limit);

        $keywords = array_map(fn($r) => $r->toArray(), $labsResults);

        $actualCost = $this->estimateCost('labs_suggestions', 1);
        $this->budgetGuard->recordCost($board, 'discover_keywords', count($keywords), $actualCost, $user);

        return ['keywords' => $keywords, 'cost_cents' => $actualCost];
    }

    /**
     * Keywords entdecken, für die eine Domain rankt.
     *
     * @return array{keywords: array, cost_cents: int}
     */
    public function discoverFromDomain(BrandsSeoBoard $board, string $domain, ?User $user = null, int $limit = 100): array
    {
        $user = $this->resolveUser($board, $user);
        $estimatedCost = $this->estimateCost('labs_ranked', 1);

        if (!$this->budgetGuard->canFetch($board, $estimatedCost)) {
            return ['keywords' => [], 'cost_cents' => 0, 'error' => 'Budget limit exceeded'];
        }

        $api = $this->resolveApiService($board);
        [$locationCode, $languageCode] = array_values($this->resolveLocationLanguage($board));

        $rankedResults = $api->getRankedKeywords($user, $domain, $locationCode, $languageCode, $limit);

        $keywords = array_map(fn($r) => $r->toArray(), $rankedResults);

        $actualCost = $this->estimateCost('labs_ranked', 1);
        $this->budgetGuard->recordCost($board, 'discover_from_domain', count($keywords), $actualCost, $user);

        return ['keywords' => $keywords, 'cost_cents' => $actualCost];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function resolveUser(BrandsSeoBoard $board, ?User $user): User
    {
        return $user ?? $board->user;
    }

    protected function resolveConnectionId(BrandsSeoBoard $board): ?int
    {
        return $board->dataforseo_config['connection_id'] ?? null;
    }

    /**
     * Gibt den DataForSeoApiService zurück, ggf. mit spezifischer Connection.
     */
    protected function resolveApiService(BrandsSeoBoard $board): DataForSeoApiService
    {
        $connectionId = $this->resolveConnectionId($board);

        return $this->dataForSeoApi->forConnection($connectionId);
    }

    /**
     * Location und Language aus Board-Config lesen, mit Defaults.
     *
     * @return array{locationCode: int|null, languageName: string|null}
     */
    protected function resolveLocationLanguage(BrandsSeoBoard $board): array
    {
        return [
            'locationCode' => $board->dataforseo_config['location_code'] ?? null,
            'languageName' => $board->dataforseo_config['language_name'] ?? null,
        ];
    }

    protected function estimateCost(string $action, int $count): int
    {
        return match ($action) {
            'search_volume' => (int) ceil($count * 5),   // ~$0.05/keyword
            'serp' => (int) ceil($count * 10),            // ~$0.10/keyword
            'labs_suggestions' => (int) ceil($count * 8), // ~$0.08/request
            'labs_ranked' => (int) ceil($count * 10),     // ~$0.10/request
            'competitors' => (int) ceil($count * 10),     // ~$0.10/request
            'on_page' => (int) ceil($count * 15),         // ~$0.15/page
            default => (int) ceil($count * 5),
        };
    }
}
