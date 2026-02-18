<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsSeoBoard;

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
