<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$seoBoard->name" icon="heroicon-o-magnifying-glass">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $seoBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zur&uuml;ck zur Marke</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-&Uuml;bersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $seoBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zur&uuml;ck zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Ansicht --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Ansicht</h3>
                    <div class="flex gap-1 p-1 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                        <button wire:click="switchView('analysis')"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $viewMode === 'analysis' ? 'bg-white text-lime-700 shadow-sm border border-lime-200' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}">
                            @svg('heroicon-o-table-cells', 'w-3.5 h-3.5')
                            Analyse
                        </button>
                        <button wire:click="switchView('kanban')"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition-all {{ $viewMode === 'kanban' ? 'bg-white text-lime-700 shadow-sm border border-lime-200' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}">
                            @svg('heroicon-o-view-columns', 'w-3.5 h-3.5')
                            Kanban
                        </button>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-seo-board-info')">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-information-circle','w-4 h-4')
                                <span>Info & Konzept</span>
                            </span>
                        </x-ui-button>
                        @can('update', $seoBoard)
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-seo-board-settings', { seoBoardId: {{ $seoBoard->id }} })">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                                    <span>Einstellungen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Statistiken</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $allKeywords->count() }}</div>
                            <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Keywords</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $clusters->count() }}</div>
                            <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Cluster</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3 text-center">
                            @php $avgSv = $allKeywords->whereNotNull('search_volume')->avg('search_volume'); @endphp
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $avgSv ? number_format($avgSv, 0) : '–' }}</div>
                            <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">&empty; SV</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $allKeywords->whereNotNull('position')->count() }}</div>
                            <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Rankings</div>
                        </div>
                    </div>
                </div>

                {{-- Budget --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Budget</h3>
                    @if($budgetSummary['limit_cents'] !== null)
                        <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs text-[var(--ui-muted)]">Verbraucht</span>
                                <span class="text-xs font-medium text-[var(--ui-secondary)]">
                                    {{ number_format($budgetSummary['spent_cents'] / 100, 2) }} / {{ number_format($budgetSummary['limit_cents'] / 100, 2) }} &euro;
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all {{ ($budgetSummary['percentage'] ?? 0) > 80 ? 'bg-red-500' : (($budgetSummary['percentage'] ?? 0) > 50 ? 'bg-yellow-500' : 'bg-lime-500') }}"
                                     style="width: {{ min($budgetSummary['percentage'] ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                    @else
                        <div class="py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-xs text-[var(--ui-muted)]">Kein Budget-Limit gesetzt</span>
                        </div>
                    @endif
                </div>

                {{-- Letzter Refresh --}}
                @if($seoBoard->last_refreshed_at)
                    <div class="py-2 px-3 bg-lime-50 border border-lime-200 rounded-lg">
                        <span class="text-xs text-lime-700">
                            @svg('heroicon-o-clock', 'w-3 h-3 inline-block mr-1')
                            Refresh: {{ $seoBoard->last_refreshed_at->diffForHumans() }}
                        </span>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivit&auml;ten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="py-8 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                        @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                    </div>
                    <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivit&auml;ten</p>
                </div>
            </div>
        </x-ui-page-sidebar>
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
                    <div class="mb-5 rounded-xl border border-emerald-200/60 bg-gradient-to-r from-emerald-50/60 to-white p-4">
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-chart-bar', 'w-5 h-5 text-emerald-600')
                                <span class="text-sm font-bold text-emerald-800">Gesamt</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                                <div>
                                    <span class="text-[10px] uppercase tracking-wide text-[var(--ui-muted)]">Cluster</span>
                                    <span class="ml-1 text-sm font-bold text-[var(--ui-secondary)] tabular-nums">{{ $clusterAnalysis->count() }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-wide text-[var(--ui-muted)]">Keywords</span>
                                    <span class="ml-1 text-sm font-bold text-[var(--ui-secondary)] tabular-nums">{{ number_format($totalKeywords) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-wide text-[var(--ui-muted)]">SV</span>
                                    <span class="ml-1 text-sm font-bold text-[var(--ui-secondary)] tabular-nums">{{ number_format($totalSv) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-wide text-[var(--ui-muted)]">KD</span>
                                    <span class="ml-1 text-sm font-bold text-[var(--ui-secondary)] tabular-nums">{{ $avgKd }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100/80 border border-emerald-200">
                                    <span class="text-[10px] uppercase tracking-wide text-emerald-700">Traffic-Wert</span>
                                    <span class="text-sm font-extrabold text-emerald-800 tabular-nums">{{ number_format($totalTrafficValue, 0) }} {{ "\u{20AC}" }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-wide text-[var(--ui-muted)]">Rankings</span>
                                    <span class="ml-1 text-sm font-bold text-[var(--ui-secondary)] tabular-nums">{{ $totalRankings }}/{{ $totalKeywords }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase tracking-wide text-[var(--ui-muted)]">Coverage</span>
                                    <span class="ml-1 text-sm font-bold text-[var(--ui-secondary)] tabular-nums">{{ $avgCoverage }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sort-Header (matches card layout) --}}
                    <div class="hidden lg:flex items-center gap-4 pl-9 pr-4 pb-3 text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                        <button wire:click="sortBy('name')" class="flex-1 min-w-0 flex items-center gap-1 hover:text-[var(--ui-secondary)] transition-colors">
                            Cluster
                            @if($sortField === 'name')
                                @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-lime-600')
                            @endif
                        </button>
                        <div class="flex items-center gap-5 flex-shrink-0">
                            <button wire:click="sortBy('opportunity_score')" class="w-28 flex items-center justify-center gap-1 hover:text-[var(--ui-secondary)] transition-colors">
                                Score
                                @if($sortField === 'opportunity_score')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-lime-600')
                                @endif
                            </button>
                            <button wire:click="sortBy('sum_sv')" class="w-16 flex items-center justify-end gap-1 hover:text-[var(--ui-secondary)] transition-colors">
                                &Sigma; SV
                                @if($sortField === 'sum_sv')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-lime-600')
                                @endif
                            </button>
                            <button wire:click="sortBy('weighted_kd')" class="w-12 flex items-center justify-end gap-1 hover:text-[var(--ui-secondary)] transition-colors">
                                KD
                                @if($sortField === 'weighted_kd')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-lime-600')
                                @endif
                            </button>
                            <button wire:click="sortBy('traffic_value')" class="w-16 flex items-center justify-end gap-1 hover:text-[var(--ui-secondary)] transition-colors">
                                Wert
                                @if($sortField === 'traffic_value')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-lime-600')
                                @endif
                            </button>
                            <button wire:click="sortBy('coverage')" class="w-24 flex items-center justify-end gap-1 hover:text-[var(--ui-secondary)] transition-colors">
                                Coverage
                                @if($sortField === 'coverage')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-lime-600')
                                @endif
                            </button>
                            <button wire:click="sortBy('avg_position')" class="w-12 flex items-center justify-end gap-1 hover:text-[var(--ui-secondary)] transition-colors">
                                Pos
                                @if($sortField === 'avg_position')
                                    @svg($sortDirection === 'asc' ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'w-3 h-3 text-lime-600')
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
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-lime-50 mb-3">
                                @svg('heroicon-o-table-cells', 'w-6 h-6 text-lime-600')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">Erstelle Cluster, um die Analyse-Ansicht zu nutzen.</p>
                        </div>
                    </div>
                @endif
            </div>

        {{-- === KANBAN VIEW === --}}
        @else
            <div class="seo-board-kanban-container flex-1 min-w-0 min-h-0 h-full">
                <x-ui-kanban-container>
                    {{-- Unzugeordnete Keywords --}}
                    @if($unclusteredKeywords->count() > 0)
                        <x-ui-kanban-column title="Ohne Cluster" :scrollable="true">
                            <x-slot name="headerActions">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">
                                    {{ $unclusteredKeywords->count() }}
                                </span>
                            </x-slot>
                            @foreach($unclusteredKeywords as $keyword)
                                @include('brands::livewire.seo-keyword-preview-card', ['keyword' => $keyword, 'maxSearchVolume' => $maxSearchVolume])
                            @endforeach
                        </x-ui-kanban-column>
                    @endif

                    {{-- Cluster als Spalten --}}
                    @foreach($clusters as $cluster)
                        @php $clusterColor = $cluster->color ?? 'gray'; @endphp
                        <x-ui-kanban-column :title="$cluster->name" :scrollable="true">
                            <x-slot name="headerActions">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-{{ $clusterColor }}-500"></span>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-{{ $clusterColor }}-50 text-{{ $clusterColor }}-700">
                                        {{ $cluster->keywords->count() }}
                                    </span>
                                </div>
                            </x-slot>
                            @foreach($cluster->keywords as $keyword)
                                @include('brands::livewire.seo-keyword-preview-card', ['keyword' => $keyword, 'maxSearchVolume' => $maxSearchVolume])
                            @endforeach
                        </x-ui-kanban-column>
                    @endforeach
                </x-ui-kanban-container>
            </div>
        @endif

    @else
        <div class="flex-1 min-w-0 flex items-center justify-center">
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center max-w-md">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-lime-50 mb-4">
                    @svg('heroicon-o-magnifying-glass', 'w-8 h-8 text-lime-600')
                </div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Keywords</h3>
                <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle Keywords und Cluster &uuml;ber die LLM-Tools, um dein SEO Board zu f&uuml;llen.</p>
                <div class="text-xs text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg p-3 border border-[var(--ui-border)]/40">
                    <p class="font-medium mb-1">Verf&uuml;gbare Tools:</p>
                    <p>brands.seo_keyword_clusters.POST</p>
                    <p>brands.seo_keywords.POST</p>
                    <p>brands.seo_keywords.BULK_POST</p>
                </div>
            </div>
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
    .seo-board-kanban-container .absolute.bottom-6 {
        display: none !important;
    }
    /* SEO Keyword Cards: lime accent */
    .seo-board-kanban-container .seo-keyword-card {
        border-left: 3px solid rgb(132 204 22); /* lime-500 */
        background: white;
    }
    .seo-board-kanban-container .seo-keyword-card:hover {
        background: rgb(247 254 231) !important; /* lime-50 */
    }
</style>
@endpush
