<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoBudgetGuardService;
use Livewire\Attributes\On;

class SeoBoard extends Component
{
    public BrandsSeoBoard $seoBoard;

    public string $viewMode = 'analysis';
    public string $sortField = 'opportunity_score';
    public string $sortDirection = 'desc';

    public function mount(BrandsSeoBoard $brandsSeoBoard)
    {
        $this->seoBoard = $brandsSeoBoard->fresh()->load('keywordClusters.keywords');

        $this->authorize('view', $this->seoBoard);
    }

    #[On('updateSeoBoard')]
    public function updateSeoBoard()
    {
        $this->seoBoard->refresh();
        $this->seoBoard->load('keywordClusters.keywords');
    }

    public function switchView(string $mode): void
    {
        if (in_array($mode, ['kanban', 'analysis'])) {
            $this->viewMode = $mode;
        }
    }

    public function sortBy(string $field): void
    {
        $allowedFields = [
            'name', 'opportunity_score', 'sum_sv', 'weighted_kd',
            'avg_cpc', 'traffic_value', 'coverage', 'rankings', 'avg_position',
        ];

        if (! in_array($field, $allowedFields)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        $user = Auth::user();

        $clusters = $this->seoBoard->keywordClusters()
            ->with(['keywords'])
            ->orderBy('order')
            ->get();

        // Alle Keywords flat für die Tabelle, nach Cluster gruppiert
        $allKeywords = $this->seoBoard->keywords()
            ->with(['cluster'])
            ->orderByRaw('COALESCE(keyword_cluster_id, 0)')
            ->orderBy('order')
            ->get();

        // Unzugeordnete Keywords (ohne Cluster)
        $unclusteredKeywords = $this->seoBoard->keywords()
            ->whereNull('keyword_cluster_id')
            ->orderBy('order')
            ->get();

        // Max Search Volume für relative Balken
        $maxSearchVolume = $allKeywords->max('search_volume') ?: 1;

        // Budget-Summary
        $budgetGuard = app(SeoBudgetGuardService::class);
        $budgetSummary = $budgetGuard->getBudgetSummary($this->seoBoard);

        // Cluster Analysis
        $clusterAnalysis = collect();

        if ($this->viewMode === 'analysis' && $clusters->count() > 0) {
            $clusterAnalysis = $clusters->map(function ($cluster) {
                $keywords = $cluster->keywords;
                $count = $keywords->count();

                if ($count === 0) {
                    return [
                        'cluster' => $cluster,
                        'keywords' => $keywords,
                        'count' => 0,
                        'sum_sv' => 0,
                        'weighted_kd' => 0,
                        'avg_cpc' => 0,
                        'traffic_value' => 0,
                        'raw_score' => 0,
                        'opportunity_score' => 0,
                        'coverage' => 0,
                        'rankings' => 0,
                        'avg_position' => null,
                    ];
                }

                $sumSv = $keywords->sum('search_volume') ?: 0;

                // SV-gewichteter KD-Durchschnitt
                $svWithKd = $keywords->filter(fn ($kw) => $kw->keyword_difficulty !== null && $kw->search_volume > 0);
                $weightedKd = $svWithKd->count() > 0
                    ? $svWithKd->sum(fn ($kw) => $kw->keyword_difficulty * $kw->search_volume) / max($svWithKd->sum('search_volume'), 1)
                    : ($keywords->whereNotNull('keyword_difficulty')->avg('keyword_difficulty') ?? 0);

                // Ø CPC in Euro
                $cpcKeywords = $keywords->whereNotNull('cpc_cents');
                $avgCpc = $cpcKeywords->count() > 0
                    ? $cpcKeywords->avg('cpc_cents') / 100
                    : 0;

                // Traffic-Wert = Σ SV × Ø CPC
                $trafficValue = $sumSv * $avgCpc;

                // Coverage: % Keywords mit content_status != 'none'
                $withContent = $keywords->filter(fn ($kw) => $kw->content_status && $kw->content_status !== 'none')->count();
                $coverage = $count > 0 ? round(($withContent / $count) * 100) : 0;

                // Rankings & Position
                $rankingKeywords = $keywords->whereNotNull('position');
                $rankings = $rankingKeywords->count();
                $avgPosition = $rankings > 0 ? round($rankingKeywords->avg('position'), 1) : null;

                // Position-Boost: Low-hanging fruit (11-20) bekommen Bonus
                $positionBoost = 1.0;
                if ($avgPosition !== null) {
                    if ($avgPosition <= 10) {
                        $positionBoost = 0.3;       // schon Top-10, wenig Handlungsbedarf
                    } elseif ($avgPosition <= 20) {
                        $positionBoost = 1.5;       // Schlagdistanz — Low-hanging fruit!
                    } elseif ($avgPosition <= 50) {
                        $positionBoost = 1.2;       // erreichbar
                    } else {
                        $positionBoost = 0.8;       // weit weg
                    }
                }

                // Multi-dimensionaler Opportunity Score:
                // Wert pro Schwierigkeitseinheit × Coverage-Lücke × Position-Boost
                $coverageGap = 1 - ($coverage / 100);
                $rawScore = ($trafficValue / max($weightedKd + 1, 1))
                          * max($coverageGap, 0.1)  // min 0.1 damit 100% Coverage nicht auf 0 fällt
                          * $positionBoost;

                return [
                    'cluster' => $cluster,
                    'keywords' => $keywords,
                    'count' => $count,
                    'sum_sv' => $sumSv,
                    'weighted_kd' => round($weightedKd, 1),
                    'avg_cpc' => round($avgCpc, 2),
                    'traffic_value' => round($trafficValue, 2),
                    'raw_score' => $rawScore,
                    'opportunity_score' => 0, // normalized below
                    'coverage' => $coverage,
                    'rankings' => $rankings,
                    'avg_position' => $avgPosition,
                    'position_boost' => $positionBoost,
                ];
            });

            // Normalize opportunity scores to 0–100
            $maxRaw = $clusterAnalysis->max('raw_score');
            if ($maxRaw > 0) {
                $clusterAnalysis = $clusterAnalysis->map(function ($item) use ($maxRaw) {
                    $item['opportunity_score'] = round(($item['raw_score'] / $maxRaw) * 100);
                    return $item;
                });
            }

            // Sort
            $clusterAnalysis = $clusterAnalysis->sortBy(
                $this->sortField === 'name'
                    ? fn ($item) => $item['cluster']->name
                    : fn ($item) => $item[$this->sortField] ?? 0,
                SORT_REGULAR,
                $this->sortDirection === 'desc'
            )->values();
        }

        return view('brands::livewire.seo-board', [
            'user' => $user,
            'clusters' => $clusters,
            'allKeywords' => $allKeywords,
            'unclusteredKeywords' => $unclusteredKeywords,
            'maxSearchVolume' => $maxSearchVolume,
            'budgetSummary' => $budgetSummary,
            'clusterAnalysis' => $clusterAnalysis,
        ])->layout('platform::layouts.app');
    }
}
