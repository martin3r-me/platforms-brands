<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="'Redaktionsplan: ' . $socialBoard->name" icon="heroicon-o-calendar-days">
            <x-slot name="actions">
                <a href="{{ route('brands.social-boards.show', $socialBoard) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zurück zum Board</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.social-boards.show', $socialBoard) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-view-columns', 'w-4 h-4')
                            <span>Board-Ansicht</span>
                        </a>
                        <a href="{{ route('brands.brands.show', $socialBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurück zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Status Filter --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Status</h3>
                    <select wire:model.live="filterStatus" class="w-full text-sm rounded-lg border border-[var(--ui-border)] bg-white px-3 py-2 text-[var(--ui-secondary)] focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]">
                        <option value="">Alle Status</option>
                        <option value="draft">Entwurf</option>
                        <option value="scheduled">Geplant</option>
                        <option value="publishing">Wird veröffentlicht</option>
                        <option value="published">Veröffentlicht</option>
                        <option value="failed">Fehlgeschlagen</option>
                    </select>
                </div>

                {{-- Platform Filter --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Plattform</h3>
                    <select wire:model.live="filterPlatform" class="w-full text-sm rounded-lg border border-[var(--ui-border)] bg-white px-3 py-2 text-[var(--ui-secondary)] focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]">
                        <option value="">Alle Plattformen</option>
                        @foreach($platforms as $platform)
                            <option value="{{ $platform->id }}">{{ $platform->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Ansicht --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Ansicht</h3>
                    <div class="flex gap-1 p-1 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                        <button wire:click="setViewMode('day')" class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $viewMode === 'day' ? 'bg-white text-[var(--ui-primary)] shadow-sm border border-[var(--ui-border)]/60' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}">
                            Tag
                        </button>
                        <button wire:click="setViewMode('week')" class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $viewMode === 'week' ? 'bg-white text-[var(--ui-primary)] shadow-sm border border-[var(--ui-border)]/60' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}">
                            Woche
                        </button>
                        <button wire:click="setViewMode('month')" class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $viewMode === 'month' ? 'bg-white text-[var(--ui-primary)] shadow-sm border border-[var(--ui-border)]/60' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}">
                            Monat
                        </button>
                    </div>
                </div>

                {{-- Board-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Typ</span>
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-[var(--ui-primary-10)] text-[var(--ui-primary)] border border-[var(--ui-primary)]/20">
                                Redaktionsplan
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Marke</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $socialBoard->brand->name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Main Content --}}
    <div class="p-6">
        {{-- Period Navigation --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <button wire:click="previousPeriod" class="p-2 rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors text-[var(--ui-secondary)]">
                    @svg('heroicon-o-chevron-left', 'w-4 h-4')
                </button>
                <button wire:click="goToToday" class="px-3 py-1.5 text-sm font-medium rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors text-[var(--ui-secondary)]">
                    Heute
                </button>
                <button wire:click="nextPeriod" class="p-2 rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors text-[var(--ui-secondary)]">
                    @svg('heroicon-o-chevron-right', 'w-4 h-4')
                </button>
                <h2 class="text-lg font-semibold text-[var(--ui-secondary)] ml-2">{{ $periodTitle }}</h2>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Timeline/Calendar Grid --}}
        <div class="space-y-1">
            @foreach($days as $day)
                @php
                    $dateKey = $day->format('Y-m-d');
                    $dayCards = $cardsByDate[$dateKey] ?? collect();
                    $isToday = $day->isToday();
                    $isWeekend = $day->isWeekend();
                @endphp
                <div class="rounded-xl border {{ $isToday ? 'border-[var(--ui-primary)]/40 bg-[var(--ui-primary-10)]/30' : ($isWeekend ? 'border-[var(--ui-border)]/30 bg-[var(--ui-muted-5)]/50' : 'border-[var(--ui-border)]/40 bg-white') }}">
                    {{-- Day Header --}}
                    <div class="flex items-center gap-3 px-4 py-2.5 border-b border-[var(--ui-border)]/20">
                        <div class="flex items-center gap-2 min-w-[140px]">
                            <span class="text-xs font-medium uppercase tracking-wide {{ $isToday ? 'text-[var(--ui-primary)]' : 'text-[var(--ui-muted)]' }}">
                                {{ $day->translatedFormat('D') }}
                            </span>
                            <span class="text-sm font-semibold {{ $isToday ? 'text-[var(--ui-primary)]' : 'text-[var(--ui-secondary)]' }}">
                                {{ $day->format('d.m.') }}
                            </span>
                            @if($isToday)
                                <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full bg-[var(--ui-primary)] text-white">
                                    Heute
                                </span>
                            @endif
                        </div>
                        <span class="text-xs text-[var(--ui-muted)]">
                            {{ $dayCards->count() }} {{ $dayCards->count() === 1 ? 'Card' : 'Cards' }}
                        </span>
                    </div>

                    {{-- Cards for this day --}}
                    @if($dayCards->count() > 0)
                        <div class="divide-y divide-[var(--ui-border)]/20">
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
                <h3 class="text-sm font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3 flex items-center gap-2">
                    @svg('heroicon-o-inbox', 'w-4 h-4')
                    Ungeplante Cards ({{ $unscheduledCards->count() }})
                </h3>
                <div class="rounded-xl border border-[var(--ui-border)]/40 bg-white divide-y divide-[var(--ui-border)]/20">
                    @foreach($unscheduledCards as $card)
                        @include('brands::livewire.editorial-plan-card-detail', ['card' => $card])
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="space-y-3 text-sm">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                            <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $activity['title'] ?? 'Aktivität' }}</div>
                            <div class="text-[var(--ui-muted)]">{{ $activity['time'] ?? '' }}</div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                                @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivitäten</p>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">Änderungen werden hier angezeigt</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
