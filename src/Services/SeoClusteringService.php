<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Models\BrandsSeoKeywordCluster;
use Platform\Core\Models\User;
use Platform\Integrations\Services\DataForSeoApiService;
use Illuminate\Support\Facades\Log;

class SeoClusteringService
{
    protected const CLUSTER_COLORS = [
        'blue', 'purple', 'green', 'amber', 'red',
        'cyan', 'orange', 'pink', 'teal', 'indigo',
    ];

    public function __construct(
        protected DataForSeoApiService $dataForSeoApi,
        protected SeoBudgetGuardService $budgetGuard,
        protected SeoKeywordService $keywordService,
    ) {}

    /**
     * Clustert ungeclusterte Keywords per SERP-URL-Overlap.
     *
     * @return array{clusters_created: int, keywords_clustered: int, keywords_fetched: int, singletons_remaining: int, cost_cents: int, clusters: array, error?: string}
     */
    public function clusterBySerp(
        BrandsSeoBoard $board,
        User $user,
        int $minOverlap = 3,
        int $maxKeywords = 300,
    ): array {
        // 1. Lade unclustered Keywords
        $keywords = $board->keywords()
            ->whereNull('keyword_cluster_id')
            ->orderByDesc('search_volume')
            ->take($maxKeywords)
            ->get();

        if ($keywords->isEmpty()) {
            return [
                'clusters_created' => 0,
                'keywords_clustered' => 0,
                'keywords_fetched' => 0,
                'singletons_remaining' => 0,
                'cost_cents' => 0,
                'clusters' => [],
            ];
        }

        // 2. Budget-Check vorab
        $estimatedCost = $this->estimateCost('serp', $keywords->count());

        if (!$this->budgetGuard->canFetch($board, $estimatedCost)) {
            return [
                'clusters_created' => 0,
                'keywords_clustered' => 0,
                'keywords_fetched' => 0,
                'singletons_remaining' => $keywords->count(),
                'cost_cents' => 0,
                'clusters' => [],
                'error' => 'Budget limit exceeded. Estimated cost: ' . $estimatedCost . ' cents for ' . $keywords->count() . ' SERP fetches.',
            ];
        }

        // 3. SERP-Fetch für jedes Keyword
        $api = $this->resolveApiService($board);
        [$locationCode, $languageName] = array_values($this->resolveLocationLanguage($board));
        $serpMap = $this->fetchSerpForKeywords($keywords, $user, $api, $locationCode, $languageName);

        $fetchedCount = count($serpMap);

        if ($fetchedCount === 0) {
            return [
                'clusters_created' => 0,
                'keywords_clustered' => 0,
                'keywords_fetched' => 0,
                'singletons_remaining' => $keywords->count(),
                'cost_cents' => 0,
                'clusters' => [],
            ];
        }

        // 4. Adjacency-Liste aufbauen
        $adjacency = $this->buildAdjacencyList($serpMap, $minOverlap);

        // 5. Connected Components finden (BFS)
        $allIds = array_keys($serpMap);
        $components = $this->findConnectedComponents($adjacency, $allIds);

        // 6. Cluster erstellen
        $keywordsById = $keywords->keyBy('id');
        $result = $this->createClusters($board, $user, $components, $keywordsById);

        // 7. Kosten erfassen
        $actualCost = $this->estimateCost('serp', $fetchedCount);
        $this->budgetGuard->recordCost($board, 'auto_cluster', $fetchedCount, $actualCost, $user);

        $singletonsRemaining = $board->keywords()
            ->whereNull('keyword_cluster_id')
            ->count();

        return [
            'clusters_created' => $result['clusters_created'],
            'keywords_clustered' => $result['keywords_clustered'],
            'keywords_fetched' => $fetchedCount,
            'singletons_remaining' => $singletonsRemaining,
            'cost_cents' => $actualCost,
            'clusters' => $result['clusters'],
        ];
    }

    /**
     * SERP-Daten für Keywords holen. Fehler pro Keyword abfangen.
     *
     * @return array<int, string[]> keyword_id => [normalized_url, ...]
     */
    protected function fetchSerpForKeywords(
        $keywords,
        User $user,
        DataForSeoApiService $api,
        ?int $locationCode,
        ?string $languageName,
    ): array {
        $serpMap = [];

        foreach ($keywords as $keyword) {
            try {
                $serpResults = $api->getSerpOrganic($user, $keyword->keyword, $locationCode, $languageName);

                if (empty($serpResults)) {
                    continue;
                }

                $urls = [];
                foreach (array_slice($serpResults, 0, 10) as $serpResult) {
                    if ($serpResult->url) {
                        $normalized = $this->normalizeUrl($serpResult->url);
                        if ($normalized) {
                            $urls[] = $normalized;
                        }
                    }
                }

                if (!empty($urls)) {
                    $serpMap[$keyword->id] = $urls;
                }
            } catch (\Throwable $e) {
                Log::warning('SeoClusteringService: SERP fetch failed for keyword', [
                    'keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $serpMap;
    }

    /**
     * URL normalisieren: Host (ohne www) + Path (ohne trailing slash, query, fragment).
     */
    protected function normalizeUrl(string $url): ?string
    {
        $parsed = parse_url($url);

        if (!isset($parsed['host'])) {
            return null;
        }

        $host = strtolower($parsed['host']);
        $host = preg_replace('/^www\./', '', $host);

        $path = rtrim($parsed['path'] ?? '', '/');

        return $host . $path;
    }

    /**
     * Adjacency-Liste aufbauen: Paarvergleich der SERP-URLs.
     *
     * @param array<int, string[]> $serpMap
     * @return array<int, int[]>
     */
    protected function buildAdjacencyList(array $serpMap, int $minOverlap): array
    {
        $ids = array_keys($serpMap);
        $adjacency = [];

        foreach ($ids as $id) {
            $adjacency[$id] = [];
        }

        $count = count($ids);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $idA = $ids[$i];
                $idB = $ids[$j];

                $overlap = count(array_intersect($serpMap[$idA], $serpMap[$idB]));

                if ($overlap >= $minOverlap) {
                    $adjacency[$idA][] = $idB;
                    $adjacency[$idB][] = $idA;
                }
            }
        }

        return $adjacency;
    }

    /**
     * BFS: Connected Components finden.
     *
     * @return array<int[]> Gruppen von keyword_ids (nur Gruppen mit > 1 Member)
     */
    protected function findConnectedComponents(array $adjacency, array $allIds): array
    {
        $visited = [];
        $components = [];

        foreach ($allIds as $id) {
            if (isset($visited[$id])) {
                continue;
            }

            $component = [];
            $queue = [$id];
            $visited[$id] = true;

            while (!empty($queue)) {
                $current = array_shift($queue);
                $component[] = $current;

                foreach ($adjacency[$current] ?? [] as $neighbor) {
                    if (!isset($visited[$neighbor])) {
                        $visited[$neighbor] = true;
                        $queue[] = $neighbor;
                    }
                }
            }

            // Nur Gruppen mit mehr als 1 Keyword
            if (count($component) > 1) {
                $components[] = $component;
            }
        }

        // Größte Cluster zuerst
        usort($components, fn($a, $b) => count($b) - count($a));

        return $components;
    }

    /**
     * Cluster-Records erstellen und Keywords zuweisen.
     */
    protected function createClusters(
        BrandsSeoBoard $board,
        User $user,
        array $components,
        $keywordsById,
    ): array {
        $clustersCreated = 0;
        $keywordsClustered = 0;
        $clusterDetails = [];

        foreach ($components as $index => $component) {
            // Name = Keyword mit höchstem Search Volume in der Gruppe
            $bestKeyword = null;
            $bestVolume = -1;
            $keywordNames = [];

            foreach ($component as $keywordId) {
                $kw = $keywordsById[$keywordId] ?? null;
                if (!$kw) {
                    continue;
                }

                $keywordNames[] = $kw->keyword;
                $volume = $kw->search_volume ?? 0;
                if ($volume > $bestVolume) {
                    $bestVolume = $volume;
                    $bestKeyword = $kw;
                }
            }

            if (!$bestKeyword) {
                continue;
            }

            $color = self::CLUSTER_COLORS[$index % count(self::CLUSTER_COLORS)];

            $cluster = $this->keywordService->createCluster($board, [
                'name' => $bestKeyword->keyword,
                'color' => $color,
            ], $user);

            // Keywords dem Cluster zuweisen
            foreach ($component as $keywordId) {
                $kw = $keywordsById[$keywordId] ?? null;
                if ($kw) {
                    $kw->update(['keyword_cluster_id' => $cluster->id]);
                    $keywordsClustered++;
                }
            }

            $clustersCreated++;
            $clusterDetails[] = [
                'name' => $cluster->name,
                'color' => $color,
                'keyword_count' => count($component),
                'keywords' => $keywordNames,
            ];
        }

        return [
            'clusters_created' => $clustersCreated,
            'keywords_clustered' => $keywordsClustered,
            'clusters' => $clusterDetails,
        ];
    }

    protected function resolveApiService(BrandsSeoBoard $board): DataForSeoApiService
    {
        $connectionId = $board->dataforseo_config['connection_id'] ?? null;

        return $this->dataForSeoApi->forConnection($connectionId);
    }

    /**
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
            'serp' => (int) ceil($count * 10),
            default => (int) ceil($count * 5),
        };
    }
}
