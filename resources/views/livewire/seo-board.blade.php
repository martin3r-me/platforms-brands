<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$seoBoard->name" icon="heroicon-o-magnifying-glass" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $seoBoard->brand->name, 'href' => route('brands.brands.show', $seoBoard->brand)],
            ['label' => $seoBoard->name],
        ]">
            <x-slot name="left">
                <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-seo-board-info')">
                    @svg('heroicon-o-information-circle', 'w-4 h-4') Info &amp; Konzept
                </x-nx-button>
                @can('update', $seoBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-seo-board-settings', { seoBoardId: {{ $seoBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
            </x-slot>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-&Uuml;bersicht" width="w-80" :defaultOpen="true">
            <div class="p-5 space-y-5">
                {{-- Ansicht --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Ansicht</h3>
                    <div class="flex flex-col gap-1 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-bg)] p-1">
                        <div class="flex gap-1">
                            <button wire:click="switchView('analysis')"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-[6px] px-3 py-1.5 text-xs font-medium transition-colors {{ $viewMode === 'analysis' ? 'bg-[color:var(--nx-surface)] text-[color:var(--nx-text)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]' : 'text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]' }}">
                                @svg('heroicon-o-table-cells', 'w-3.5 h-3.5')
                                Analyse
                            </button>
                            <button wire:click="switchView('kanban')"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-[6px] px-3 py-1.5 text-xs font-medium transition-colors {{ $viewMode === 'kanban' ? 'bg-[color:var(--nx-surface)] text-[color:var(--nx-text)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]' : 'text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]' }}">
                                @svg('heroicon-o-view-columns', 'w-3.5 h-3.5')
                                Kanban
                            </button>
                        </div>
                        <button wire:click="switchView('competitors')"
                                class="inline-flex items-center justify-center gap-1.5 rounded-[6px] px-3 py-1.5 text-xs font-medium transition-colors {{ $viewMode === 'competitors' ? 'bg-[color:var(--nx-surface)] text-[color:var(--nx-text)] border border-[color:var(--nx-line)] shadow-[var(--nx-shadow-card)]' : 'text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]' }}">
                            @svg('heroicon-o-globe-alt', 'w-3.5 h-3.5')
                            Wettbewerber
                        </button>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Statistiken</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3 text-center">
                            <div class="text-lg font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $allKeywords->count() }}</div>
                            <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Keywords</div>
                        </div>
                        <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3 text-center">
                            <div class="text-lg font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $clusters->count() }}</div>
                            <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Cluster</div>
                        </div>
                        <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3 text-center">
                            @php $avgSv = $allKeywords->whereNotNull('search_volume')->avg('search_volume'); @endphp
                            <div class="text-lg font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $avgSv ? number_format($avgSv, 0) : '–' }}</div>
                            <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">&empty; SV</div>
                        </div>
                        <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3 text-center">
                            <div class="text-lg font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $allKeywords->whereNotNull('position')->count() }}</div>
                            <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Rankings</div>
                        </div>
                    </div>
                </div>

                {{-- Budget --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Budget</h3>
                    @if($budgetSummary['limit_cents'] !== null)
                        <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs text-[color:var(--nx-faint)]">Verbraucht</span>
                                <span class="text-xs font-medium tabular-nums text-[color:var(--nx-text)]">
                                    {{ number_format($budgetSummary['spent_cents'] / 100, 2) }} / {{ number_format($budgetSummary['limit_cents'] / 100, 2) }} &euro;
                                </span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-[color:var(--nx-hover)]">
                                <div class="h-2 rounded-full transition-all {{ ($budgetSummary['percentage'] ?? 0) > 80 ? 'bg-[color:var(--nx-danger)]' : (($budgetSummary['percentage'] ?? 0) > 50 ? 'bg-[color:var(--nx-warning)]' : 'bg-[color:var(--nx-accent)]') }}"
                                     style="width: {{ min($budgetSummary['percentage'] ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2">
                            <span class="text-xs text-[color:var(--nx-faint)]">Kein Budget-Limit gesetzt</span>
                        </div>
                    @endif
                </div>

                {{-- Letzter Refresh --}}
                @if($seoBoard->last_refreshed_at)
                    <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-bg)] px-3 py-2">
                        <span class="inline-flex items-center gap-1.5 text-xs text-[color:var(--nx-muted)]">
                            @svg('heroicon-o-clock', 'w-3 h-3')
                            Refresh: {{ $seoBoard->last_refreshed_at->diffForHumans() }}
                        </span>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        @include('brands::partials.board-activity')
    </x-slot>

    {{-- Board Content --}}
    @if($clusters->count() > 0 || $unclusteredKeywords->count() > 0)

        {{-- === ANALYSIS VIEW === --}}
        @if($viewMode === 'analysis')
            <div class="flex-1 min-w-0 overflow-y-auto p-4 sm:p-6">
                @if($clusterAnalysis->count() > 0)
                    {{-- Gesamt-Summary --}}
                    @php
                        $totalKeywords = $clusterAnalysis->sum('count');
                        $totalSv = $clusterAnalysis->sum('sum_sv');
                        $totalTrafficValue = $clusterAnalysis->sum('traffic_value');
                        $totalRankings = $clusterAnalysis->sum('rankings');
                        $avgCoverage = $totalKeywords > 0 ? round($clusterAnalysis->sum(fn($c) => $c['coverage'] * $c['count']) / $totalKeywords) : 0;
                        $avgKd = $totalSv > 0 ? round($clusterAnalysis->sum(fn($c) => $c['weighted_kd'] * $c['sum_sv']) / $totalSv, 1) : 0;
                    @endphp
                    <x-nx-card class="mb-5">
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-chart-bar', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                <span class="text-sm font-semibold text-[color:var(--nx-text)]">Gesamt</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Cluster</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $clusterAnalysis->count() }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Keywords</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-text)]">{{ number_format($totalKeywords) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">SV</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-text)]">{{ number_format($totalSv) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">KD</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $avgKd }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Traffic-Wert</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-text)]">{{ number_format($totalTrafficValue, 0) }} {{ "\u{20AC}" }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Rankings</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $totalRankings }}/{{ $totalKeywords }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Coverage</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $avgCoverage }}%</span>
                                </div>
                            </div>
                        </div>
                    </x-nx-card>

                    {{-- Sort-Header (matches card layout) --}}
                    <div class="hidden lg:flex items-center gap-4 pl-9 pr-4 pb-3 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">
                        <button wire:click="sortBy('name')" class="flex-1 min-w-0 flex items-center gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                            Cluster
                            @if($sortField === 'name')
                                @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                            @endif
                        </button>
                        <div class="flex items-center gap-5 flex-shrink-0">
                            <button wire:click="sortBy('opportunity_score')" class="w-28 flex items-center justify-center gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                Score
                                @if($sortField === 'opportunity_score')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                            <button wire:click="sortBy('sum_sv')" class="w-16 flex items-center justify-end gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                &Sigma; SV
                                @if($sortField === 'sum_sv')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                            <button wire:click="sortBy('weighted_kd')" class="w-12 flex items-center justify-end gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                KD
                                @if($sortField === 'weighted_kd')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                            <button wire:click="sortBy('traffic_value')" class="w-16 flex items-center justify-end gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                Wert
                                @if($sortField === 'traffic_value')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                            <button wire:click="sortBy('coverage')" class="w-24 flex items-center justify-end gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                Coverage
                                @if($sortField === 'coverage')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                            <button wire:click="sortBy('avg_position')" class="w-12 flex items-center justify-end gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                Pos
                                @if($sortField === 'avg_position')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                        </div>
                    </div>

                    {{-- Cluster Cards --}}
                    <div class="space-y-2">
                        @foreach($clusterAnalysis as $data)
                            @include('brands::livewire.seo-cluster-analysis-row', ['data' => $data])
                        @endforeach
                    </div>
                @else
                    <x-nx-empty icon="heroicon-o-table-cells">Erstelle Cluster, um die Analyse-Ansicht zu nutzen.</x-nx-empty>
                @endif
            </div>

        {{-- === COMPETITOR VIEW === --}}
        @elseif($viewMode === 'competitors')
            <div class="flex-1 min-w-0 overflow-y-auto p-4 sm:p-6">
                @if($competitorAnalysis->count() > 0)
                    {{-- Gesamt-Summary --}}
                    @php
                        $totalDomains = $competitorAnalysis->count();
                        $totalGaps = $competitorAnalysis->sum('gap_count');
                        $totalOverlap = $competitorAnalysis->sum('overlap_count');
                        $totalVisibility = $competitorAnalysis->sum('visibility_score');
                    @endphp
                    <x-nx-card class="mb-5">
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-globe-alt', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                <span class="text-sm font-semibold text-[color:var(--nx-text)]">Wettbewerber</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Domains</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-text)]">{{ $totalDomains }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Overlap</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-text)]">{{ number_format($totalOverlap) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Gaps</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-danger)]">{{ number_format($totalGaps) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--nx-faint)]">Visibility</span>
                                    <span class="ml-1 text-sm font-semibold tabular-nums text-[color:var(--nx-text)]">{{ number_format($totalVisibility) }}</span>
                                </div>
                            </div>
                        </div>
                    </x-nx-card>

                    {{-- Sort-Header --}}
                    <div class="hidden lg:flex items-center gap-4 pl-9 pr-4 pb-3 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">
                        <button wire:click="sortBy('domain')" class="flex-1 min-w-0 flex items-center gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                            Domain
                            @if($sortField === 'domain')
                                @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                            @endif
                        </button>
                        <div class="flex items-center gap-5 flex-shrink-0">
                            <button wire:click="sortBy('keyword_count')" class="w-16 flex items-center justify-end gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                Keywords
                                @if($sortField === 'keyword_count')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                            <button wire:click="sortBy('avg_serp_position')" class="w-12 flex items-center justify-end gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                {{ "\u{00D8}" }} Pos
                                @if($sortField === 'avg_serp_position')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                            <button wire:click="sortBy('overlap_count')" class="w-16 flex items-center justify-end gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                Overlap
                                @if($sortField === 'overlap_count')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                            <button wire:click="sortBy('gap_count')" class="w-12 flex items-center justify-end gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                Gaps
                                @if($sortField === 'gap_count')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                            <button wire:click="sortBy('visibility_score')" class="w-20 flex items-center justify-end gap-1 transition-colors hover:text-[color:var(--nx-text)]">
                                Visibility
                                @if($sortField === 'visibility_score')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-[color:var(--nx-accent)]')
                                @endif
                            </button>
                        </div>
                    </div>

                    {{-- Domain Rows --}}
                    <div class="space-y-2">
                        @foreach($competitorAnalysis as $domainData)
                            @include('brands::livewire.seo-competitor-domain-row', [
                                'data' => $domainData,
                                'strategicCompetitorMap' => $strategicCompetitorMap,
                            ])
                        @endforeach
                    </div>
                @else
                    <x-nx-empty icon="heroicon-o-globe-alt">
                        Keine Wettbewerber-Daten vorhanden. Competitor-Daten werden automatisch beim Keyword-Refresh erfasst.
                    </x-nx-empty>
                @endif
            </div>

        {{-- === KANBAN VIEW === --}}
        @else
            <div class="seo-board-kanban-container flex-1 min-w-0 min-h-0 h-full">
                <x-nx-kanban-container>
                    {{-- Unzugeordnete Keywords --}}
                    @if($unclusteredKeywords->count() > 0)
                        <x-nx-kanban-column title="Ohne Cluster" :count="$unclusteredKeywords->count()" :scrollable="true">
                            @foreach($unclusteredKeywords as $keyword)
                                @include('brands::livewire.seo-keyword-preview-card', ['keyword' => $keyword, 'maxSearchVolume' => $maxSearchVolume])
                            @endforeach
                        </x-nx-kanban-column>
                    @endif

                    {{-- Cluster als Spalten --}}
                    @foreach($clusters as $cluster)
                        @php
                            $clusterColor = $cluster->color ?? 'gray';
                            $toneMap = [
                                'red' => 'rose', 'rose' => 'rose', 'orange' => 'amber', 'amber' => 'amber', 'yellow' => 'amber',
                                'green' => 'emerald', 'emerald' => 'emerald', 'lime' => 'emerald', 'teal' => 'teal',
                                'cyan' => 'sky', 'sky' => 'sky', 'blue' => 'sky', 'indigo' => 'indigo', 'violet' => 'violet',
                                'purple' => 'violet', 'pink' => 'pink', 'fuchsia' => 'pink', 'gray' => 'slate', 'slate' => 'slate',
                            ];
                            $clusterTone = $toneMap[$clusterColor] ?? 'slate';
                        @endphp
                        <x-nx-kanban-column :title="$cluster->name" :tone="$clusterTone" :count="$cluster->keywords->count()" :scrollable="true">
                            @foreach($cluster->keywords as $keyword)
                                @include('brands::livewire.seo-keyword-preview-card', ['keyword' => $keyword, 'maxSearchVolume' => $maxSearchVolume])
                            @endforeach
                        </x-nx-kanban-column>
                    @endforeach
                </x-nx-kanban-container>
            </div>
        @endif

    @else
        <div class="flex-1 min-w-0 flex items-center justify-center">
            <x-nx-empty icon="heroicon-o-magnifying-glass">
                Noch keine Keywords – erstelle Keywords und Cluster &uuml;ber die LLM-Tools, um dein SEO Board zu f&uuml;llen.
            </x-nx-empty>
        </div>
    @endif

    {{-- Settings Modal --}}
    <livewire:brands.seo-board-settings-modal/>

    {{-- Info Modal --}}
    <livewire:brands.seo-board-info-modal/>
</x-ui-page>

@push('styles')
<style>
    /* Toggle-Button im SEO Board verstecken */
    .seo-board-kanban-container .absolute.bottom-3 {
        display: none !important;
    }
    /* SEO Keyword Cards: nx accent */
    .seo-board-kanban-container .seo-keyword-card {
        border-left: 3px solid var(--nx-accent);
        background: var(--nx-surface);
    }
    .seo-board-kanban-container .seo-keyword-card:hover {
        background: var(--nx-hover) !important;
    }
</style>
@endpush
