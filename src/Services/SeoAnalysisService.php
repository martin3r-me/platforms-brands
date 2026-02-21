<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeywordPosition;

class SeoAnalysisService
{
    /**
     * Wettbewerber-Lücken-Analyse basierend auf Keyword-Daten.
     */
    public function getCompetitorGapAnalysis(BrandsSeoBoard $board): array
    {
        $keywords = $board->keywords()->with('cluster')->get();

        $gaps = [];
        foreach ($keywords as $keyword) {
            if ($keyword->position === null || $keyword->position > 10) {
                $gaps[] = [
                    'keyword' => $keyword->keyword,
                    'cluster' => $keyword->cluster?->name,
                    'search_volume' => $keyword->search_volume,
                    'keyword_difficulty' => $keyword->keyword_difficulty,
                    'current_position' => $keyword->position,
                    'opportunity_score' => $this->calculateOpportunityScore($keyword),
                ];
            }
        }

        usort($gaps, fn($a, $b) => ($b['opportunity_score'] ?? 0) <=> ($a['opportunity_score'] ?? 0));

        return $gaps;
    }

    /**
     * Persona-basiertes Keyword-Mapping.
     */
    public function getPersonaKeywordMapping(BrandsSeoBoard $board): array
    {
        $clusters = $board->keywordClusters()->with('keywords')->get();

        $mapping = [];
        foreach ($clusters as $cluster) {
            $mapping[] = [
                'cluster' => $cluster->name,
                'color' => $cluster->color,
                'keywords_count' => $cluster->keywords->count(),
                'avg_search_volume' => (int) $cluster->keywords->avg('search_volume'),
                'avg_difficulty' => (int) $cluster->keywords->avg('keyword_difficulty'),
                'intents' => $cluster->keywords->pluck('search_intent')->filter()->countBy()->toArray(),
            ];
        }

        return $mapping;
    }

    /**
     * Content-Chancen basierend auf Keywords.
     */
    public function getContentOpportunities(BrandsSeoBoard $board): array
    {
        $keywords = $board->keywords()
            ->whereNotNull('search_volume')
            ->orderByDesc('search_volume')
            ->get();

        $opportunities = [];
        foreach ($keywords as $keyword) {
            if ($keyword->content_idea || ($keyword->keyword_difficulty !== null && $keyword->keyword_difficulty < 50)) {
                $opportunities[] = [
                    'keyword' => $keyword->keyword,
                    'search_volume' => $keyword->search_volume,
                    'keyword_difficulty' => $keyword->keyword_difficulty,
                    'content_idea' => $keyword->content_idea,
                    'search_intent' => $keyword->search_intent,
                    'priority' => $keyword->priority,
                ];
            }
        }

        return $opportunities;
    }

    /**
     * Zusammenfassende Statistiken des SEO Boards.
     */
    public function getKeywordSummary(BrandsSeoBoard $board): array
    {
        $keywords = $board->keywords;

        return [
            'total_keywords' => $keywords->count(),
            'clusters_count' => $board->keywordClusters()->count(),
            'avg_search_volume' => (int) $keywords->avg('search_volume'),
            'avg_difficulty' => (int) $keywords->avg('keyword_difficulty'),
            'total_search_volume' => (int) $keywords->sum('search_volume'),
            'intents' => $keywords->pluck('search_intent')->filter()->countBy()->toArray(),
            'types' => $keywords->pluck('keyword_type')->filter()->countBy()->toArray(),
            'priorities' => $keywords->pluck('priority')->filter()->countBy()->toArray(),
            'with_metrics' => $keywords->whereNotNull('search_volume')->count(),
            'without_metrics' => $keywords->whereNull('search_volume')->count(),
            'last_refreshed_at' => $board->last_refreshed_at?->toIso8601String(),
        ];
    }

    /**
     * Ranking-Trend-Analyse basierend auf Position-History.
     * Klassifiziert Keywords als: Aufsteiger, Absteiger, Stabil, Neu eingestiegen.
     */
    public function getRankingTrends(BrandsSeoBoard $board, int $days = 30): array
    {
        $keywords = $board->keywords()->with('cluster')->get();
        $since = now()->subDays($days);

        $trends = [
            'rising' => [],
            'falling' => [],
            'stable' => [],
            'new_entries' => [],
            'no_data' => [],
        ];

        foreach ($keywords as $keyword) {
            $snapshots = BrandsSeoKeywordPosition::where('seo_keyword_id', $keyword->id)
                ->where('tracked_at', '>=', $since)
                ->orderBy('tracked_at')
                ->get();

            if ($snapshots->isEmpty()) {
                $trends['no_data'][] = [
                    'keyword' => $keyword->keyword,
                    'cluster' => $keyword->cluster?->name,
                    'current_position' => $keyword->position,
                ];
                continue;
            }

            $firstSnapshot = $snapshots->first();
            $lastSnapshot = $snapshots->last();
            $positionChange = $firstSnapshot->position - $lastSnapshot->position; // positive = aufgestiegen

            $entry = [
                'keyword' => $keyword->keyword,
                'cluster' => $keyword->cluster?->name,
                'current_position' => $lastSnapshot->position,
                'start_position' => $firstSnapshot->position,
                'position_change' => $positionChange,
                'snapshots_count' => $snapshots->count(),
                'best_position' => $snapshots->min('position'),
                'worst_position' => $snapshots->max('position'),
            ];

            // Klassifizierung: Erster Snapshot hat keine previous_position = "Neu eingestiegen"
            if ($firstSnapshot->previous_position === null && $snapshots->count() <= 2) {
                $trends['new_entries'][] = $entry;
            } elseif ($positionChange > 2) {
                $trends['rising'][] = $entry;
            } elseif ($positionChange < -2) {
                $trends['falling'][] = $entry;
            } else {
                $trends['stable'][] = $entry;
            }
        }

        // Sortieren: Aufsteiger nach größtem Anstieg, Absteiger nach größtem Verlust
        usort($trends['rising'], fn($a, $b) => $b['position_change'] <=> $a['position_change']);
        usort($trends['falling'], fn($a, $b) => $a['position_change'] <=> $b['position_change']);

        return [
            'period_days' => $days,
            'since' => $since->toIso8601String(),
            'summary' => [
                'rising_count' => count($trends['rising']),
                'falling_count' => count($trends['falling']),
                'stable_count' => count($trends['stable']),
                'new_entries_count' => count($trends['new_entries']),
                'no_data_count' => count($trends['no_data']),
            ],
            'rising' => $trends['rising'],
            'falling' => $trends['falling'],
            'stable' => $trends['stable'],
            'new_entries' => $trends['new_entries'],
        ];
    }

    /**
     * Competitor-Gap-Analyse: Keywords wo Competitors ranken, wir aber nicht.
     * Basiert auf der seo_keyword_competitors Sub-Table.
     */
    public function getCompetitorGaps(BrandsSeoBoard $board): array
    {
        $keywords = $board->keywords()->with(['cluster', 'competitors'])->get();

        $gaps = [];
        $domains = [];

        foreach ($keywords as $keyword) {
            if ($keyword->competitors->isEmpty()) {
                continue;
            }

            $isGap = empty($keyword->published_url) || $keyword->position === null;

            foreach ($keyword->competitors as $comp) {
                $domains[$comp->domain] = ($domains[$comp->domain] ?? 0) + 1;
            }

            if ($isGap) {
                $gaps[] = [
                    'keyword' => $keyword->keyword,
                    'keyword_id' => $keyword->id,
                    'cluster' => $keyword->cluster?->name,
                    'search_volume' => $keyword->search_volume,
                    'keyword_difficulty' => $keyword->keyword_difficulty,
                    'our_position' => $keyword->position,
                    'published_url' => $keyword->published_url,
                    'competitors' => $keyword->competitors->map(fn ($c) => [
                        'domain' => $c->domain,
                        'url' => $c->url,
                        'position' => $c->position,
                    ])->values()->toArray(),
                    'best_competitor_position' => $keyword->competitors->min('position'),
                    'opportunity_score' => $this->calculateOpportunityScore($keyword),
                ];
            }
        }

        usort($gaps, fn($a, $b) => ($b['opportunity_score'] ?? 0) <=> ($a['opportunity_score'] ?? 0));

        arsort($domains);

        $totalKeywords = $keywords->count();
        $keywordsWithCompetitors = $keywords->filter(fn ($kw) => $kw->competitors->isNotEmpty())->count();

        return [
            'gaps' => $gaps,
            'gaps_count' => count($gaps),
            'total_keywords' => $totalKeywords,
            'keywords_with_competitors' => $keywordsWithCompetitors,
            'top_competitor_domains' => array_slice($domains, 0, 10, true),
        ];
    }

    protected function calculateOpportunityScore(mixed $keyword): float
    {
        $volume = $keyword->search_volume ?? 0;
        $difficulty = $keyword->keyword_difficulty ?? 50;

        if ($volume === 0) {
            return 0;
        }

        return round(($volume / max($difficulty, 1)) * 10, 2);
    }
}
