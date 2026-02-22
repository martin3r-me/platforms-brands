<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$socialBoard->name" icon="heroicon-o-share">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $socialBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
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
                        <a href="{{ route('brands.social-boards.editorial-plan', $socialBoard) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-calendar-days', 'w-4 h-4')
                            <span>Redaktionsplan</span>
                        </a>
                        <a href="{{ route('brands.brands.show', $socialBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurück zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $socialBoard)
                            <x-ui-button variant="secondary" size="sm" wire:click="createSlot">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-square-2-stack','w-4 h-4')
                                    <span>Slot</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-social-board-settings', { socialBoardId: {{ $socialBoard->id }} })">
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
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-[var(--ui-primary-10)] text-[var(--ui-primary)] border border-[var(--ui-primary)]/20">
                                Social Board
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $socialBoard->created_at->format('d.m.Y') }}
                            </span>
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

    {{-- Board-Container: füllt restliche Breite, Spalten scrollen intern --}}
    @if($slots->count() > 0)
        <div class="social-board-kanban-container">
            <x-ui-kanban-container sortable="updateSlotOrder" sortable-group="updateCardOrder">
                @foreach($slots as $slot)
                    <x-ui-kanban-column :title="$slot->name" :sortable-id="$slot->id" :scrollable="true">
                        <x-slot name="headerActions">
                            @can('update', $socialBoard)
                                <button 
                                    wire:click="createCard({{ $slot->id }})" 
                                    class="text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                                    title="Neue Card"
                                >
                                    @svg('heroicon-o-plus-circle', 'w-4 h-4')
                                </button>
                                <button 
                                    @click="$dispatch('open-modal-social-board-slot-settings', { slotId: {{ $slot->id }} })"
                                    class="text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                                    title="Einstellungen"
                                >
                                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                </button>
                            @endcan
                        </x-slot>

                        @foreach($slot->cards as $card)
                            @include('brands::livewire.social-card-preview-card', ['card' => $card])
                        @endforeach
                    </x-ui-kanban-column>
                @endforeach
            </x-ui-kanban-container>
        </div>
    @else
        <div class="flex items-center justify-center h-full">
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center max-w-md">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                    @svg('heroicon-o-view-columns', 'w-8 h-8 text-[var(--ui-muted)]')
                </div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Slots</h3>
                <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle deinen ersten Slot für dieses Social Board.</p>
                @can('update', $socialBoard)
                    <x-ui-button variant="primary" size="sm" wire:click="createSlot">
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Slot erstellen</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>
        </div>
    @endif

    {{-- Modals innerhalb des Page-Roots halten (ein Root-Element) --}}
    <livewire:brands.social-board-settings-modal/>
    <livewire:brands.social-board-slot-settings-modal/>
</x-ui-page>

@push('styles')
<style>
    /* Toggle-Button im Social Board verstecken */
    .social-board-kanban-container .absolute.bottom-6 {
        display: none !important;
    }
</style>
@endpush
