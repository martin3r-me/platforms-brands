<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$ctaBoard->name" icon="heroicon-o-cursor-arrow-rays" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $ctaBoard->brand->name, 'href' => route('brands.brands.show', $ctaBoard->brand)],
            ['label' => $ctaBoard->name],
        ]">
            <x-slot name="left">
                <div class="inline-flex items-center rounded-[6px] border border-[color:var(--nx-line)] p-0.5">
                    <button wire:click="setGroupBy('funnel_stage')"
                        class="rounded-[4px] px-2.5 py-1 text-xs font-medium transition-colors {{ $groupBy === 'funnel_stage' ? 'bg-[color:var(--nx-hover)] text-[color:var(--nx-text)]' : 'text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]' }}">
                        Funnel Stage
                    </button>
                    <button wire:click="setGroupBy('type')"
                        class="rounded-[4px] px-2.5 py-1 text-xs font-medium transition-colors {{ $groupBy === 'type' ? 'bg-[color:var(--nx-hover)] text-[color:var(--nx-text)]' : 'text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]' }}">
                        Typ
                    </button>
                </div>
            </x-slot>

            @can('update', $ctaBoard)
                <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-cta-board-settings', { ctaBoardId: {{ $ctaBoard->id }} })">
                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-5 space-y-5">
                {{-- Board-Details --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Details</h3>
                    <div class="overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] divide-y divide-[color:var(--nx-line)]">
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Typ</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">CTA Board</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">CTAs gesamt</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $ctas->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Aktiv</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $ctas->where('is_active', true)->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Erstellt</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $ctaBoard->created_at->format('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Filter</h3>
                    <div class="space-y-3">
                        {{-- Type Filter --}}
                        <div>
                            <label class="mb-1 block text-[11px] text-[color:var(--nx-faint)]">Typ</label>
                            <select wire:model.live="filterType" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-2.5 py-1.5 text-xs text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                                <option value="">Alle</option>
                                <option value="primary">Primary</option>
                                <option value="secondary">Secondary</option>
                                <option value="micro">Micro</option>
                            </select>
                        </div>

                        {{-- Funnel Stage Filter --}}
                        <div>
                            <label class="mb-1 block text-[11px] text-[color:var(--nx-faint)]">Funnel Stage</label>
                            <select wire:model.live="filterFunnelStage" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-2.5 py-1.5 text-xs text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                                <option value="">Alle</option>
                                <option value="awareness">Awareness</option>
                                <option value="consideration">Consideration</option>
                                <option value="decision">Decision</option>
                            </select>
                        </div>

                        {{-- Active Filter --}}
                        <div>
                            <label class="mb-1 block text-[11px] text-[color:var(--nx-faint)]">Status</label>
                            <select wire:model.live="filterIsActive" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-2.5 py-1.5 text-xs text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
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
        @include('brands::partials.board-activity')
    </x-slot>

    {{-- Board-Container --}}
    <x-ui-page-container spacing="space-y-8" width="contained">
        @if($ctas->count() > 0)
            @if($groupBy === 'funnel_stage')
                @foreach($grouped as $stage => $stageCtas)
                    @php
                        $stageLabels = [
                            'awareness' => 'Awareness',
                            'consideration' => 'Consideration',
                            'decision' => 'Decision',
                        ];
                        $stageVariants = [
                            'awareness' => 'info',
                            'consideration' => 'warning',
                            'decision' => 'success',
                        ];
                        $stageLabel = $stageLabels[$stage] ?? ucfirst($stage);
                        $stageVariant = $stageVariants[$stage] ?? 'neutral';
                    @endphp
                    <x-nx-section :title="$stageLabel" :hint="$stageCtas->count()">
                        <x-nx-card flush class="divide-y divide-[color:var(--nx-line)]">
                            @foreach($stageCtas as $cta)
                                @include('brands::livewire.cta-row', ['cta' => $cta])
                            @endforeach
                        </x-nx-card>
                    </x-nx-section>
                @endforeach
            @else
                {{-- Group by type --}}
                @foreach($grouped as $typeKey => $typeCtas)
                    @php
                        $typeLabels = [
                            'primary' => 'Primary',
                            'secondary' => 'Secondary',
                            'micro' => 'Micro',
                        ];
                        $typeLabel = $typeLabels[$typeKey] ?? ucfirst($typeKey);
                    @endphp
                    <x-nx-section :title="$typeLabel" :hint="$typeCtas->count()">
                        <x-nx-card flush class="divide-y divide-[color:var(--nx-line)]">
                            @foreach($typeCtas as $cta)
                                @include('brands::livewire.cta-row', ['cta' => $cta])
                            @endforeach
                        </x-nx-card>
                    </x-nx-section>
                @endforeach
            @endif
        @else
            <x-nx-empty icon="heroicon-o-cursor-arrow-rays">
                Noch keine CTAs – erstelle CTAs über die LLM-Tools (brands.ctas.POST · brands.ctas.BULK_POST), um dein CTA Board zu füllen.
            </x-nx-empty>
        @endif
    </x-ui-page-container>

    {{-- Settings Modal --}}
    <livewire:brands.cta-board-settings-modal/>
</x-ui-page>
