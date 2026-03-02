<?php

namespace Platform\Brands\Services;

use Illuminate\Support\Collection;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefRanking;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Core\Models\User;
use Platform\Integrations\Services\DataForSeoApiService;

class ContentBriefRankingService
{
    public function __construct(
        protected DataForSeoApiService $dataForSeoApi,
        protected SeoBudgetGuardService $budgetGuard,
    ) {}

    /**
     * Trackt Rankings für ein einzelnes Content Brief Board.
     * Prüft alle Keywords in den verknüpften Keyword-Clustern gegen die target_url.
     * Unterstützt Multi-Region: Wenn auf dem SEO Board mehrere Locations konfiguriert sind,
     * wird pro Keyword pro Location ein SERP-Call gemacht.
     *
     * @return array{tracked: int, not_found: int, matched: int, cost_cents: int, locations_tracked: int, error?: string}
     */
    public function trackBriefRankings(BrandsContentBriefBoard $brief, ?User $user = null): array
    {
        $targetUrl = $brief->target_url;

        if (!$targetUrl) {
            return ['tracked' => 0, 'not_found' => 0, 'matched' => 0, 'cost_cents' => 0, 'locations_tracked' => 0, 'error' => 'Keine target_url gesetzt.'];
        }

        // Keywords über Keyword-Cluster-Verknüpfungen sammeln
        $keywords = $this->collectKeywordsForBrief($brief);

        if ($keywords->isEmpty()) {
            return ['tracked' => 0, 'not_found' => 0, 'matched' => 0, 'cost_cents' => 0, 'locations_tracked' => 0, 'error' => 'Keine Keywords mit dem Brief verknüpft.'];
        }

        // SEO Board für Budget + API-Config ermitteln
        $seoBoard = $this->resolveSeoBoard($brief);
        if (!$seoBoard) {
            return ['tracked' => 0, 'not_found' => 0, 'matched' => 0, 'cost_cents' => 0, 'locations_tracked' => 0, 'error' => 'Kein SEO Board gefunden.'];
        }

        // Locations ermitteln (Multi-Region oder Fallback auf einzelne Location)
        $locations = $seoBoard->getLocations();
        if (empty($locations)) {
            // Kein Location konfiguriert → Default
            $locations = [['code' => null, 'label' => 'Default']];
        }

        $user = $user ?? $seoBoard->user;
        $totalSerpCalls = $keywords->count() * count($locations);
        $estimatedCost = $this->estimateCost($totalSerpCalls);

        if (!$this->budgetGuard->canFetch($seoBoard, $estimatedCost)) {
            return ['tracked' => 0, 'not_found' => 0, 'matched' => 0, 'cost_cents' => 0, 'locations_tracked' => 0, 'error' => 'Budget-Limit erreicht.'];
        }

        $api = $this->resolveApiService($seoBoard);
        $config = $seoBoard->dataforseo_config ?? [];
        $languageName = $config['language_name'] ?? null;
        $targetHost = parse_url($targetUrl, PHP_URL_HOST);
        $trackedCount = 0;
        $notFoundCount = 0;
        $matchedCount = 0;

        foreach ($locations as $location) {
            $locationCode = $location['code'] ?? null;
            $locationLabel = $location['label'] ?? 'Unknown';

            foreach ($keywords as $keyword) {
                $serpResults = $api->getSerpOrganic($user, $keyword->keyword, $locationCode, $languageName);

                // Vorherige Position für dieses Brief+Keyword+Location ermitteln
                $lastRanking = BrandsContentBriefRanking::where('content_brief_board_id', $brief->id)
                    ->where('seo_keyword_id', $keyword->id)
                    ->where('location', $locationLabel)
                    ->orderByDesc('tracked_at')
                    ->first();

                $position = null;
                $foundUrl = null;
                $isTargetMatch = false;
                $serpFeatures = [];

                if (!empty($serpResults)) {
                    foreach ($serpResults as $serpResult) {
                        $serpFeatures[] = $serpResult->domain;

                        // Eigene Domain im SERP finden
                        if ($targetHost && str_contains($serpResult->url ?? '', $targetHost)) {
                            if ($position === null) {
                                $position = $serpResult->position;
                                $foundUrl = $serpResult->url;

                                $normalizedTarget = rtrim($targetUrl, '/');
                                $normalizedFound = rtrim($serpResult->url ?? '', '/');
                                $isTargetMatch = ($normalizedTarget === $normalizedFound);
                            }
                        }
                    }
                }

                BrandsContentBriefRanking::create([
                    'content_brief_board_id' => $brief->id,
                    'seo_keyword_id' => $keyword->id,
                    'position' => $position,
                    'previous_position' => $lastRanking?->position,
                    'target_url' => $targetUrl,
                    'found_url' => $foundUrl,
                    'is_target_match' => $isTargetMatch,
                    'serp_features' => array_unique(array_slice($serpFeatures, 0, 10)),
                    'cost_cents' => 10,
                    'search_engine' => 'google',
                    'device' => 'desktop',
                    'location' => $locationLabel,
                    'tracked_at' => now(),
                ]);

                $trackedCount++;

                if ($position === null) {
                    $notFoundCount++;
                } elseif ($isTargetMatch) {
                    $matchedCount++;
                }
            }
        }

        $actualCost = $this->estimateCost($trackedCount);
        $this->budgetGuard->recordCost($seoBoard, 'brief_rankings', $trackedCount, $actualCost, $user);

        return [
            'tracked' => $trackedCount,
            'not_found' => $notFoundCount,
            'matched' => $matchedCount,
            'cost_cents' => $actualCost,
            'locations_tracked' => count($locations),
            'brief_id' => $brief->id,
            'brief_name' => $brief->name,
            'target_url' => $targetUrl,
        ];
    }

    /**
     * Trackt Rankings für alle veröffentlichten Briefs mit target_url.
     */
    public function trackAllPublishedBriefs(?int $teamId = null): array
    {
        $query = BrandsContentBriefBoard::query()
            ->whereNotNull('target_url')
            ->where('target_url', '!=', '')
            ->where('status', 'published');

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        $briefs = $query->get();
        $results = [];
        $totalTracked = 0;
        $totalCost = 0;

        foreach ($briefs as $brief) {
            $result = $this->trackBriefRankings($brief);
            $results[] = $result;
            $totalTracked += $result['tracked'];
            $totalCost += $result['cost_cents'];
        }

        return [
            'briefs_processed' => $briefs->count(),
            'total_keywords_tracked' => $totalTracked,
            'total_cost_cents' => $totalCost,
            'results' => $results,
        ];
    }

    /**
     * Gibt die neuesten Rankings für ein Brief zurück, gruppiert nach Keyword.
     */
    public function getLatestRankings(BrandsContentBriefBoard $brief): Collection
    {
        // Neueste tracked_at für dieses Brief
        $latestDate = BrandsContentBriefRanking::where('content_brief_board_id', $brief->id)
            ->max('tracked_at');

        if (!$latestDate) {
            return collect();
        }

        return BrandsContentBriefRanking::where('content_brief_board_id', $brief->id)
            ->where('tracked_at', $latestDate)
            ->with('seoKeyword')
            ->orderBy('position')
            ->get();
    }

    /**
     * Gibt Ranking-Verlauf für ein Brief über Zeit zurück, gruppiert nach Datum und Location.
     */
    public function getRankingHistory(BrandsContentBriefBoard $brief, int $limit = 12): Collection
    {
        return BrandsContentBriefRanking::where('content_brief_board_id', $brief->id)
            ->selectRaw('DATE(tracked_at) as date, location, COUNT(*) as keywords_tracked, AVG(position) as avg_position, SUM(CASE WHEN is_target_match = 1 THEN 1 ELSE 0 END) as matched_count, SUM(CASE WHEN position IS NULL THEN 1 ELSE 0 END) as not_found_count')
            ->groupByRaw('DATE(tracked_at), location')
            ->orderByDesc('date')
            ->limit($limit)
            ->get();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Sammelt alle Keywords die über Keyword-Cluster mit dem Brief verknüpft sind.
     */
    protected function collectKeywordsForBrief(BrandsContentBriefBoard $brief): Collection
    {
        $brief->load('briefKeywordClusters.keywordCluster.keywords');

        $keywords = collect();

        foreach ($brief->briefKeywordClusters as $link) {
            if ($link->keywordCluster && $link->keywordCluster->keywords) {
                foreach ($link->keywordCluster->keywords as $keyword) {
                    $keywords->put($keyword->id, $keyword); // dedupliziert per ID
                }
            }
        }

        return $keywords->values();
    }

    /**
     * Ermittelt das SEO Board für ein Brief (über seo_board_id oder erstes Board der Brand).
     */
    protected function resolveSeoBoard(BrandsContentBriefBoard $brief): ?BrandsSeoBoard
    {
        if ($brief->seo_board_id) {
            return BrandsSeoBoard::find($brief->seo_board_id);
        }

        // Fallback: erstes SEO Board der Brand
        return BrandsSeoBoard::where('brand_id', $brief->brand_id)->first();
    }

    protected function resolveApiService(BrandsSeoBoard $board): DataForSeoApiService
    {
        $connectionId = $board->dataforseo_config['connection_id'] ?? null;
        return $this->dataForSeoApi->forConnection($connectionId);
    }

    protected function resolveLocationLanguage(BrandsSeoBoard $board): array
    {
        return [
            'locationCode' => $board->dataforseo_config['location_code'] ?? null,
            'languageName' => $board->dataforseo_config['language_name'] ?? null,
        ];
    }

    protected function estimateCost(int $keywordCount): int
    {
        return (int) ceil($keywordCount * 10); // ~$0.10 pro SERP-Call
    }
}
