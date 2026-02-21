<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$ctaBoard->name" icon="heroicon-o-cursor-arrow-rays">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $ctaBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
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
                        <a href="{{ route('brands.brands.show', $ctaBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurück zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $ctaBoard)
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-cta-board-settings', { ctaBoardId: {{ $ctaBoard->id }} })">
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
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-violet-50 text-violet-700 border border-violet-200">
                                CTA Board
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">CTAs gesamt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $ctas->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Aktiv</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $ctas->where('is_active', true)->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $ctaBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Gruppierung --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Gruppierung</h3>
                    <div class="flex gap-2">
                        <button wire:click="setGroupBy('target_page')"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors {{ $groupBy === 'target_page' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-[var(--ui-muted)] border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]' }}">
                            Zielseite
                        </button>
                        <button wire:click="setGroupBy('funnel_stage')"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors {{ $groupBy === 'funnel_stage' ? 'bg-violet-50 text-violet-700 border-violet-200' : 'text-[var(--ui-muted)] border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]' }}">
                            Funnel Stage
                        </button>
                    </div>
                </div>

                {{-- Filter --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Filter</h3>
                    <div class="space-y-3">
                        {{-- Type Filter --}}
                        <div>
                            <label class="block text-xs text-[var(--ui-muted)] mb-1">Typ</label>
                            <select wire:model.live="filterType" class="w-full text-xs rounded-lg border border-[var(--ui-border)]/40 bg-white px-2.5 py-1.5 text-[var(--ui-secondary)]">
                                <option value="">Alle</option>
                                <option value="primary">Primary</option>
                                <option value="secondary">Secondary</option>
                                <option value="micro">Micro</option>
                            </select>
                        </div>

                        {{-- Funnel Stage Filter --}}
                        <div>
                            <label class="block text-xs text-[var(--ui-muted)] mb-1">Funnel Stage</label>
                            <select wire:model.live="filterFunnelStage" class="w-full text-xs rounded-lg border border-[var(--ui-border)]/40 bg-white px-2.5 py-1.5 text-[var(--ui-secondary)]">
                                <option value="">Alle</option>
                                <option value="awareness">Awareness</option>
                                <option value="consideration">Consideration</option>
                                <option value="decision">Decision</option>
                            </select>
                        </div>

                        {{-- Active Filter --}}
                        <div>
                            <label class="block text-xs text-[var(--ui-muted)] mb-1">Status</label>
                            <select wire:model.live="filterIsActive" class="w-full text-xs rounded-lg border border-[var(--ui-border)]/40 bg-white px-2.5 py-1.5 text-[var(--ui-secondary)]">
                                <option value="">Alle</option>
                                <option value="1">Aktiv</option>
                                <option value="0">Inaktiv</option>
                            </select>
                        </div>
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
    @if($ctas->count() > 0)
        <div class="cta-board-kanban-container">
            <x-ui-kanban-container>
                @if($groupBy === 'funnel_stage')
                    @foreach($grouped as $stage => $stageCtas)
                        @php
                            $stageLabels = [
                                'awareness' => 'Awareness',
                                'consideration' => 'Consideration',
                                'decision' => 'Decision',
                            ];
                            $stageColors = [
                                'awareness' => 'blue',
                                'consideration' => 'amber',
                                'decision' => 'green',
                            ];
                            $stageLabel = $stageLabels[$stage] ?? ucfirst($stage);
                            $stageColor = $stageColors[$stage] ?? 'gray';
                        @endphp
                        <x-ui-kanban-column :title="$stageLabel" :scrollable="true">
                            <x-slot name="headerActions">
                                <span class="inline-block w-3 h-3 rounded-full bg-{{ $stageColor }}-500"></span>
                            </x-slot>

                            @foreach($stageCtas as $cta)
                                @include('brands::livewire.cta-preview-card', ['cta' => $cta])
                            @endforeach
                        </x-ui-kanban-column>
                    @endforeach
                @else
                    {{-- Group by target_page --}}
                    @foreach($grouped as $groupKey => $groupCtas)
                        @php
                            if (str_starts_with($groupKey, 'page_')) {
                                $firstCta = $groupCtas->first();
                                $columnTitle = $firstCta?->targetPage?->title ?? $firstCta?->targetPage?->name ?? 'Seite #' . str_replace('page_', '', $groupKey);
                            } elseif (str_starts_with($groupKey, 'url_')) {
                                $columnTitle = str_replace('url_', '', $groupKey);
                                $columnTitle = Str::limit($columnTitle, 40);
                            } else {
                                $columnTitle = 'Ohne Zielseite';
                            }
                        @endphp
                        <x-ui-kanban-column :title="$columnTitle" :scrollable="true">
                            @foreach($groupCtas as $cta)
                                @include('brands::livewire.cta-preview-card', ['cta' => $cta])
                            @endforeach
                        </x-ui-kanban-column>
                    @endforeach
                @endif
            </x-ui-kanban-container>
        </div>
    @else
        <div class="flex items-center justify-center h-full">
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center max-w-md">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-50 mb-4">
                    @svg('heroicon-o-cursor-arrow-rays', 'w-8 h-8 text-violet-600')
                </div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine CTAs</h3>
                <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle CTAs über die LLM-Tools, um dein CTA Board zu füllen.</p>
                <div class="text-xs text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg p-3 border border-[var(--ui-border)]/40">
                    <p class="font-medium mb-1">Verfügbare Tools:</p>
                    <p>brands.ctas.POST</p>
                    <p>brands.ctas.BULK_POST</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Settings Modal --}}
    <livewire:brands.cta-board-settings-modal/>
</x-ui-page>

@push('styles')
<style>
    .cta-board-kanban-container .absolute.bottom-6 {
        display: none !important;
    }
</style>
@endpush
