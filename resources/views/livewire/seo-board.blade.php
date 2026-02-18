<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$seoBoard->name" icon="heroicon-o-magnifying-glass">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $seoBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zurück zur Marke</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $seoBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurück zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
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

                {{-- Board-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Typ</span>
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-lime-50 text-lime-700 border border-lime-200">
                                SEO Board
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Keywords</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $seoBoard->keywords()->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Cluster</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $clusters->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $seoBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Budget --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Budget</h3>
                    <div class="space-y-2">
                        @if($budgetSummary['limit_cents'] !== null)
                            <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs text-[var(--ui-muted)]">Verbraucht</span>
                                    <span class="text-xs font-medium text-[var(--ui-secondary)]">
                                        {{ number_format($budgetSummary['spent_cents'] / 100, 2) }} / {{ number_format($budgetSummary['limit_cents'] / 100, 2) }} €
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all {{ ($budgetSummary['percentage'] ?? 0) > 80 ? 'bg-red-500' : (($budgetSummary['percentage'] ?? 0) > 50 ? 'bg-yellow-500' : 'bg-lime-500') }}"
                                         style="width: {{ min($budgetSummary['percentage'] ?? 0, 100) }}%"></div>
                                </div>
                                <div class="text-right mt-1">
                                    <span class="text-[10px] text-[var(--ui-muted)]">{{ $budgetSummary['percentage'] ?? 0 }}%</span>
                                </div>
                            </div>
                        @else
                            <div class="py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-xs text-[var(--ui-muted)]">Kein Budget-Limit gesetzt</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="space-y-3 text-sm">
                    <div class="py-8 text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                            @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                        </div>
                        <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivitäten</p>
                        <p class="text-xs text-[var(--ui-muted)] mt-1">Änderungen werden hier angezeigt</p>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Board-Container --}}
    @if($clusters->count() > 0 || $unclusteredKeywords->count() > 0)
        <div class="seo-board-kanban-container">
            <x-ui-kanban-container>
                {{-- Unzugeordnete Keywords --}}
                @if($unclusteredKeywords->count() > 0)
                    <x-ui-kanban-column title="Ohne Cluster" :scrollable="true">
                        @foreach($unclusteredKeywords as $keyword)
                            @include('brands::livewire.seo-keyword-preview-card', ['keyword' => $keyword])
                        @endforeach
                    </x-ui-kanban-column>
                @endif

                {{-- Cluster als Spalten --}}
                @foreach($clusters as $cluster)
                    <x-ui-kanban-column :title="$cluster->name" :scrollable="true">
                        <x-slot name="headerActions">
                            @if($cluster->color)
                                <span class="inline-block w-3 h-3 rounded-full bg-{{ $cluster->color }}-500"></span>
                            @endif
                        </x-slot>

                        @foreach($cluster->keywords as $keyword)
                            @include('brands::livewire.seo-keyword-preview-card', ['keyword' => $keyword])
                        @endforeach
                    </x-ui-kanban-column>
                @endforeach
            </x-ui-kanban-container>
        </div>
    @else
        <div class="flex items-center justify-center h-full">
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center max-w-md">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-lime-50 mb-4">
                    @svg('heroicon-o-magnifying-glass', 'w-8 h-8 text-lime-600')
                </div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Keywords</h3>
                <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle Keywords und Cluster über die LLM-Tools, um dein SEO Board zu füllen.</p>
                <div class="text-xs text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg p-3 border border-[var(--ui-border)]/40">
                    <p class="font-medium mb-1">Verfügbare Tools:</p>
                    <p>brands.seo_keyword_clusters.POST</p>
                    <p>brands.seo_keywords.POST</p>
                    <p>brands.seo_keywords.BULK_POST</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Settings Modal --}}
    <livewire:brands.seo-board-settings-modal/>
</x-ui-page>

@push('styles')
<style>
    .seo-board-kanban-container .absolute.bottom-6 {
        display: none !important;
    }
</style>
@endpush
