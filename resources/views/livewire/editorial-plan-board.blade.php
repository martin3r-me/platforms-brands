<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="'Redaktionsplan: ' . $socialBoard->name" icon="heroicon-o-calendar-days" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $socialBoard->brand->name, 'href' => route('brands.brands.show', $socialBoard->brand)],
            ['label' => $socialBoard->name, 'href' => route('brands.social-boards.show', $socialBoard)],
            ['label' => 'Redaktionsplan'],
        ]" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-6">
                {{-- Status Filter --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Status</h3>
                    <x-nx-input-select name="filterStatus" wire:model.live="filterStatus" nullable nullLabel="Alle Status" :options="[
                        ['value' => 'draft', 'label' => 'Entwurf'],
                        ['value' => 'scheduled', 'label' => 'Geplant'],
                        ['value' => 'publishing', 'label' => 'Wird veröffentlicht'],
                        ['value' => 'published', 'label' => 'Veröffentlicht'],
                        ['value' => 'failed', 'label' => 'Fehlgeschlagen'],
                    ]" />
                </div>

                {{-- Platform Filter --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Plattform</h3>
                    <x-nx-input-select name="filterPlatform" wire:model.live="filterPlatform" nullable nullLabel="Alle Plattformen" :options="$platforms" optionValue="id" optionLabel="name" />
                </div>

                {{-- Ansicht --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Ansicht</h3>
                    <div class="flex gap-1 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-hover)] p-1">
                        @foreach(['day' => 'Tag', 'week' => 'Woche', 'month' => 'Monat'] as $mode => $modeLabel)
                            <button wire:click="setViewMode('{{ $mode }}')" class="flex-1 rounded-[6px] px-3 py-1.5 text-xs font-medium transition-colors {{ $viewMode === $mode ? 'bg-[color:var(--nx-surface)] text-[color:var(--nx-text)] border border-[color:var(--nx-line)]' : 'text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]' }}">
                                {{ $modeLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Board-Details --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Details</h3>
                    <div class="overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] divide-y divide-[color:var(--nx-line)]">
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Typ</span>
                            <x-nx-badge variant="accent">Redaktionsplan</x-nx-badge>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Marke</span>
                            <span class="min-w-0 truncate text-right text-[13px] font-medium text-[color:var(--nx-text)]">{{ $socialBoard->brand->name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Main Content --}}
    <div class="p-6">
        {{-- Period Navigation --}}
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button wire:click="previousPeriod" class="rounded-[6px] border border-[color:var(--nx-line-strong)] p-2 text-[color:var(--nx-text)] transition-colors hover:bg-[color:var(--nx-hover)]">
                    @svg('heroicon-o-chevron-left', 'w-4 h-4')
                </button>
                <button wire:click="goToToday" class="rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-1.5 text-sm font-medium text-[color:var(--nx-text)] transition-colors hover:bg-[color:var(--nx-hover)]">
                    Heute
                </button>
                <button wire:click="nextPeriod" class="rounded-[6px] border border-[color:var(--nx-line-strong)] p-2 text-[color:var(--nx-text)] transition-colors hover:bg-[color:var(--nx-hover)]">
                    @svg('heroicon-o-chevron-right', 'w-4 h-4')
                </button>
                <h2 class="ml-2 text-lg font-semibold text-[color:var(--nx-text)]">{{ $periodTitle }}</h2>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-4">
                <x-nx-callout variant="success">{{ session('success') }}</x-nx-callout>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4">
                <x-nx-callout variant="danger">{{ session('error') }}</x-nx-callout>
            </div>
        @endif

        {{-- Timeline/Calendar Grid --}}
        <div class="space-y-1.5">
            @foreach($days as $day)
                @php
                    $dateKey = $day->format('Y-m-d');
                    $dayCards = $cardsByDate[$dateKey] ?? collect();
                    $isToday = $day->isToday();
                    $isWeekend = $day->isWeekend();
                @endphp
                <div class="rounded-[8px] border {{ $isToday ? 'border-[color:var(--nx-accent)] bg-[color:var(--nx-hover)]' : ($isWeekend ? 'border-[color:var(--nx-line)] bg-[color:var(--nx-hover)]' : 'border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]') }}">
                    {{-- Day Header --}}
                    <div class="flex items-center gap-3 border-b border-[color:var(--nx-line)] px-4 py-2.5">
                        <div class="flex min-w-[140px] items-center gap-2">
                            <span class="text-xs font-medium uppercase tracking-wide {{ $isToday ? 'text-[color:var(--nx-text)]' : 'text-[color:var(--nx-faint)]' }}">
                                {{ $day->translatedFormat('D') }}
                            </span>
                            <span class="text-sm font-semibold text-[color:var(--nx-text)]">
                                {{ $day->format('d.m.') }}
                            </span>
                            @if($isToday)
                                <x-nx-badge variant="accent">Heute</x-nx-badge>
                            @endif
                        </div>
                        <span class="text-xs text-[color:var(--nx-faint)]">
                            {{ $dayCards->count() }} {{ $dayCards->count() === 1 ? 'Card' : 'Cards' }}
                        </span>
                    </div>

                    {{-- Cards for this day --}}
                    @if($dayCards->count() > 0)
                        <div class="divide-y divide-[color:var(--nx-line)]">
                            @foreach($dayCards as $card)
                                @include('brands::livewire.editorial-plan-card-detail', ['card' => $card])
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Unscheduled Cards --}}
        @if($unscheduledCards->count() > 0)
            <div class="mt-8">
                <h3 class="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">
                    @svg('heroicon-o-inbox', 'w-4 h-4')
                    Ungeplante Cards ({{ $unscheduledCards->count() }})
                </h3>
                <x-nx-card flush>
                    <div class="divide-y divide-[color:var(--nx-line)]">
                        @foreach($unscheduledCards as $card)
                            @include('brands::livewire.editorial-plan-card-detail', ['card' => $card])
                        @endforeach
                    </div>
                </x-nx-card>
            </div>
        @endif
    </div>

</x-ui-page>
