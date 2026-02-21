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

    /**
     * Quick Wins: Keywords mit hohem Suchvolumen, niedriger Difficulty und ohne Content.
     */
    public function getQuickWins(BrandsSeoBoard $board): array
    {
        $keywords = $board->keywords()->with('cluster')
            ->whereNotNull('search_volume')
            ->where('search_volume', '>', 0)
            ->where(function ($q) {
                $q->where('keyword_difficulty', '<', 40)
                    ->orWhereNull('keyword_difficulty');
            })
            ->where(function ($q) {
                $q->where('content_status', 'none')
                    ->orWhereNull('content_status');
            })
            ->orderByDesc('search_volume')
            ->get();

        $quickWins = [];
        foreach ($keywords as $keyword) {
            $quickWins[] = [
                'keyword' => $keyword->keyword,
                'keyword_id' => $keyword->id,
                'cluster' => $keyword->cluster?->name,
                'search_volume' => $keyword->search_volume,
                'keyword_difficulty' => $keyword->keyword_difficulty,
                'search_intent' => $keyword->search_intent,
                'keyword_type' => $keyword->keyword_type,
                'location' => $keyword->location,
                'opportunity_score' => $this->calculateOpportunityScore($keyword),
            ];
        }

        usort($quickWins, fn($a, $b) => ($b['opportunity_score'] ?? 0) <=> ($a['opportunity_score'] ?? 0));

        return [
            'quick_wins' => $quickWins,
            'count' => count($quickWins),
            'total_search_volume' => array_sum(array_column($quickWins, 'search_volume')),
            'recommendation' => count($quickWins) > 0
                ? 'Diese Keywords haben hohes Traffic-Potenzial bei niedriger Konkurrenz und noch keinen Content. Priorisiere die Keywords mit dem höchsten Opportunity Score für schnelle Rankings.'
                : 'Keine Quick Wins gefunden. Alle Keywords mit niedrigem Wettbewerb haben bereits Content.',
        ];
    }

    /**
     * Content Gaps: Keywords ohne Content, gruppiert nach Cluster.
     */
    public function getContentGaps(BrandsSeoBoard $board): array
    {
        $keywords = $board->keywords()->with('cluster')
            ->where(function ($q) {
                $q->where('content_status', 'none')
                    ->orWhere('content_status', 'planned')
                    ->orWhereNull('content_status');
            })
            ->get();

        $clusterGaps = [];
        foreach ($keywords as $keyword) {
            $clusterName = $keyword->cluster?->name ?? '(Ohne Cluster)';
            if (!isset($clusterGaps[$clusterName])) {
                $clusterGaps[$clusterName] = [
                    'cluster' => $clusterName,
                    'keywords' => [],
                    'total_search_volume' => 0,
                    'status_none' => 0,
                    'status_planned' => 0,
                ];
            }

            $clusterGaps[$clusterName]['keywords'][] = [
                'keyword' => $keyword->keyword,
                'keyword_id' => $keyword->id,
                'search_volume' => $keyword->search_volume,
                'keyword_difficulty' => $keyword->keyword_difficulty,
                'content_status' => $keyword->content_status ?? 'none',
                'search_intent' => $keyword->search_intent,
            ];
            $clusterGaps[$clusterName]['total_search_volume'] += ($keyword->search_volume ?? 0);

            $status = $keyword->content_status ?? 'none';
            if ($status === 'none') {
                $clusterGaps[$clusterName]['status_none']++;
            } else {
                $clusterGaps[$clusterName]['status_planned']++;
            }
        }

        // Sortiere Cluster nach total_search_volume
        $clusterGaps = array_values($clusterGaps);
        usort($clusterGaps, fn($a, $b) => $b['total_search_volume'] <=> $a['total_search_volume']);

        // Keywords pro Cluster nach search_volume sortieren
        foreach ($clusterGaps as &$cluster) {
            usort($cluster['keywords'], fn($a, $b) => ($b['search_volume'] ?? 0) <=> ($a['search_volume'] ?? 0));
        }
        unset($cluster);

        $totalGaps = $keywords->count();
        $totalNone = $keywords->where('content_status', 'none')->count() + $keywords->whereNull('content_status')->count();
        $totalPlanned = $keywords->where('content_status', 'planned')->count();

        return [
            'clusters' => $clusterGaps,
            'clusters_with_gaps' => count($clusterGaps),
            'total_gaps' => $totalGaps,
            'total_status_none' => $totalNone,
            'total_status_planned' => $totalPlanned,
            'recommendation' => $totalGaps > 0
                ? "Es gibt {$totalGaps} Keywords ohne fertigen Content ({$totalNone}x kein Content, {$totalPlanned}x geplant). Fokussiere auf Cluster mit dem höchsten Suchvolumen-Potenzial."
                : 'Alle Keywords haben bereits Content. Keine Content-Lücken gefunden.',
        ];
    }

    /**
     * Declining: Keywords deren Position in den letzten N Tagen um >5 Positionen gefallen ist.
     */
    public function getDeclining(BrandsSeoBoard $board, int $days = 30): array
    {
        $keywords = $board->keywords()->with('cluster')->get();
        $since = now()->subDays($days);

        $declining = [];

        foreach ($keywords as $keyword) {
            $snapshots = BrandsSeoKeywordPosition::where('seo_keyword_id', $keyword->id)
                ->where('tracked_at', '>=', $since)
                ->orderBy('tracked_at')
                ->get();

            if ($snapshots->count() < 2) {
                continue;
            }

            $firstSnapshot = $snapshots->first();
            $lastSnapshot = $snapshots->last();
            $positionChange = $firstSnapshot->position - $lastSnapshot->position; // negative = gefallen

            if ($positionChange < -5) {
                $declining[] = [
                    'keyword' => $keyword->keyword,
                    'keyword_id' => $keyword->id,
                    'cluster' => $keyword->cluster?->name,
                    'search_volume' => $keyword->search_volume,
                    'start_position' => $firstSnapshot->position,
                    'current_position' => $lastSnapshot->position,
                    'position_change' => $positionChange,
                    'content_status' => $keyword->content_status,
                    'published_url' => $keyword->published_url,
                    'snapshots_count' => $snapshots->count(),
                    'urgency' => $keyword->search_volume && $keyword->search_volume > 1000 ? 'high' : 'normal',
                ];
            }
        }

        // Sortieren nach stärkstem Verlust
        usort($declining, fn($a, $b) => $a['position_change'] <=> $b['position_change']);

        $highUrgency = array_filter($declining, fn($d) => $d['urgency'] === 'high');

        return [
            'declining' => $declining,
            'count' => count($declining),
            'high_urgency_count' => count($highUrgency),
            'period_days' => $days,
            'since' => $since->toIso8601String(),
            'recommendation' => count($declining) > 0
                ? count($highUrgency) . ' Keywords mit hohem Suchvolumen verlieren Rankings. Content-Optimierung und technisches SEO-Audit für diese Seiten priorisieren.'
                : 'Keine Keywords mit signifikantem Ranking-Verlust (>5 Positionen) im Zeitraum gefunden.',
        ];
    }

    /**
     * Defend: Top-Rankings schützen – Keywords mit Position 1-3 und hohem Suchvolumen.
     */
    public function getDefend(BrandsSeoBoard $board): array
    {
        $keywords = $board->keywords()->with('cluster')
            ->whereNotNull('position')
            ->where('position', '>=', 1)
            ->where('position', '<=', 3)
            ->orderByDesc('search_volume')
            ->get();

        $defend = [];
        foreach ($keywords as $keyword) {
            $defend[] = [
                'keyword' => $keyword->keyword,
                'keyword_id' => $keyword->id,
                'cluster' => $keyword->cluster?->name,
                'search_volume' => $keyword->search_volume,
                'position' => $keyword->position,
                'keyword_difficulty' => $keyword->keyword_difficulty,
                'content_status' => $keyword->content_status,
                'published_url' => $keyword->published_url,
                'traffic_value' => $keyword->search_volume && $keyword->cpc_cents
                    ? round(($keyword->search_volume * $keyword->cpc_cents / 100) * $this->estimateCtr($keyword->position), 2)
                    : null,
            ];
        }

        $totalTrafficValue = array_sum(array_filter(array_column($defend, 'traffic_value')));

        return [
            'defend' => $defend,
            'count' => count($defend),
            'total_estimated_traffic_value' => round($totalTrafficValue, 2),
            'recommendation' => count($defend) > 0
                ? count($defend) . ' Keywords in Top 3 mit geschätztem Traffic-Wert von ' . number_format($totalTrafficValue, 2, ',', '.') . ' €/Monat. Regelmäßig Content aktualisieren und Backlinks pflegen, um diese Positionen zu halten.'
                : 'Keine Keywords in den Top 3. Fokussiere zuerst auf Quick Wins und Content Gaps, um Top-Rankings aufzubauen.',
        ];
    }

    /**
     * Cluster Health: Coverage und Gesundheit pro Cluster.
     */
    public function getClusterHealth(BrandsSeoBoard $board): array
    {
        $clusters = $board->keywordClusters()->with(['keywords' => function ($q) {
            $q->with('cluster');
        }])->get();

        $health = [];
        foreach ($clusters as $cluster) {
            $keywords = $cluster->keywords;
            $total = $keywords->count();

            if ($total === 0) {
                $health[] = [
                    'cluster' => $cluster->name,
                    'color' => $cluster->color,
                    'keywords_count' => 0,
                    'coverage_score' => 0,
                    'avg_position' => null,
                    'content_status_distribution' => [],
                    'recommendation' => 'Cluster ist leer. Keywords hinzufügen.',
                ];
                continue;
            }

            $contentStatuses = $keywords->groupBy(fn($kw) => $kw->content_status ?? 'none')
                ->map->count()
                ->toArray();

            $withContent = ($contentStatuses['published'] ?? 0) + ($contentStatuses['optimized'] ?? 0);
            $coverageScore = round(($withContent / $total) * 100, 1);

            $withPosition = $keywords->whereNotNull('position');
            $avgPosition = $withPosition->isNotEmpty()
                ? round($withPosition->avg('position'), 1)
                : null;

            $clusterEntry = [
                'cluster' => $cluster->name,
                'color' => $cluster->color,
                'keywords_count' => $total,
                'coverage_score' => $coverageScore,
                'avg_position' => $avgPosition,
                'avg_search_volume' => (int) $keywords->avg('search_volume'),
                'avg_difficulty' => (int) $keywords->avg('keyword_difficulty'),
                'total_search_volume' => (int) $keywords->sum('search_volume'),
                'content_status_distribution' => $contentStatuses,
                'with_position' => $withPosition->count(),
                'without_position' => $total - $withPosition->count(),
            ];

            // Empfehlung pro Cluster
            if ($coverageScore < 25) {
                $clusterEntry['health'] = 'critical';
                $clusterEntry['recommendation'] = "Nur {$coverageScore}% Content-Abdeckung. Dringend Content für dieses Cluster erstellen.";
            } elseif ($coverageScore < 50) {
                $clusterEntry['health'] = 'needs_work';
                $clusterEntry['recommendation'] = "{$coverageScore}% Content-Abdeckung. Weitere Content-Stücke planen.";
            } elseif ($coverageScore < 75) {
                $clusterEntry['health'] = 'moderate';
                $clusterEntry['recommendation'] = "{$coverageScore}% Content-Abdeckung. Lücken gezielt schließen.";
            } else {
                $clusterEntry['health'] = 'good';
                $clusterEntry['recommendation'] = "{$coverageScore}% Content-Abdeckung. Bestehenden Content optimieren und Rankings überwachen.";
            }

            $health[] = $clusterEntry;
        }

        // Sortieren nach Coverage Score aufsteigend (schlechteste zuerst)
        usort($health, fn($a, $b) => $a['coverage_score'] <=> $b['coverage_score']);

        $avgCoverage = count($health) > 0
            ? round(array_sum(array_column($health, 'coverage_score')) / count($health), 1)
            : 0;

        return [
            'clusters' => $health,
            'clusters_count' => count($health),
            'avg_coverage_score' => $avgCoverage,
            'recommendation' => $avgCoverage < 50
                ? "Durchschnittliche Content-Abdeckung liegt bei nur {$avgCoverage}%. Fokussiere auf Cluster mit der niedrigsten Coverage."
                : "Durchschnittliche Content-Abdeckung bei {$avgCoverage}%. Schwache Cluster gezielt stärken.",
        ];
    }

    /**
     * Local Opportunities: Keywords mit Ortsbezug ohne Content.
     */
    public function getLocalOpportunities(BrandsSeoBoard $board): array
    {
        $keywords = $board->keywords()->with('cluster')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->where(function ($q) {
                $q->where('content_status', 'none')
                    ->orWhereNull('content_status');
            })
            ->orderByDesc('search_volume')
            ->get();

        $byLocation = [];
        foreach ($keywords as $keyword) {
            $loc = $keyword->location;
            if (!isset($byLocation[$loc])) {
                $byLocation[$loc] = [
                    'location' => $loc,
                    'keywords' => [],
                    'total_search_volume' => 0,
                ];
            }

            $byLocation[$loc]['keywords'][] = [
                'keyword' => $keyword->keyword,
                'keyword_id' => $keyword->id,
                'cluster' => $keyword->cluster?->name,
                'search_volume' => $keyword->search_volume,
                'keyword_difficulty' => $keyword->keyword_difficulty,
                'keyword_type' => $keyword->keyword_type,
                'search_intent' => $keyword->search_intent,
                'opportunity_score' => $this->calculateOpportunityScore($keyword),
            ];
            $byLocation[$loc]['total_search_volume'] += ($keyword->search_volume ?? 0);
        }

        $byLocation = array_values($byLocation);
        usort($byLocation, fn($a, $b) => $b['total_search_volume'] <=> $a['total_search_volume']);

        return [
            'locations' => $byLocation,
            'locations_count' => count($byLocation),
            'total_local_keywords' => $keywords->count(),
            'total_search_volume' => (int) $keywords->sum('search_volume'),
            'recommendation' => count($byLocation) > 0
                ? count($byLocation) . ' Standorte mit ' . $keywords->count() . ' lokalen Keywords ohne Content. Lokale Landing Pages erstellen, um regionales Suchvolumen abzudecken.'
                : 'Keine lokalen Keywords ohne Content gefunden. Prüfe ob weitere lokale Keywords recherchiert werden sollten.',
        ];
    }

    /**
     * Geschätzte CTR basierend auf Position.
     */
    protected function estimateCtr(int $position): float
    {
        return match (true) {
            $position === 1 => 0.316,
            $position === 2 => 0.158,
            $position === 3 => 0.094,
            $position <= 5  => 0.06,
            $position <= 10 => 0.03,
            default => 0.01,
        };
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
