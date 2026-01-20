<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$multiContentBoard->name" icon="heroicon-o-squares-2x2">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $multiContentBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
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
                        <a href="{{ route('brands.brands.show', $multiContentBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurück zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $multiContentBoard)
                            <x-ui-button variant="secondary" size="sm" wire:click="createSlot">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-square-2-stack','w-4 h-4')
                                    <span>Slot</span>
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
                                Multi-Content-Board
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $multiContentBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Board-Container: füllt restliche Breite, Spalten scrollen intern --}}
    @if($slots->count() > 0)
        <div class="multi-content-board-kanban-container">
            <x-ui-kanban-container sortable="updateSlotOrder" sortable-group="updateContentBoardOrder">
                @foreach($slots as $slot)
                    <x-ui-kanban-column :title="$slot->name" :sortable-id="$slot->id" :scrollable="true">
                        <x-slot name="headerActions">
                            @can('update', $multiContentBoard)
                                <button 
                                    wire:click="createContentBoard({{ $slot->id }})" 
                                    class="text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                                    title="Neues Content Board"
                                >
                                    @svg('heroicon-o-plus-circle', 'w-4 h-4')
                                </button>
                            @endcan
                        </x-slot>

                        @foreach($slot->contentBoards as $contentBoard)
                            @include('brands::livewire.content-board-preview-card', ['contentBoard' => $contentBoard])
                        @endforeach
                    </x-ui-kanban-column>
                @endforeach
            </x-ui-kanban-container>
        </div>
    @else
        <div class="flex items-center justify-center h-full">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                    @svg('heroicon-o-squares-2x2', 'w-8 h-8 text-[var(--ui-muted)]')
                </div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Slots</h3>
                <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle deinen ersten Slot für dieses Multi-Content-Board.</p>
                @can('update', $multiContentBoard)
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
</x-ui-page>

<style>
    /* Kanban-Container für Multi-Content-Board */
    .multi-content-board-kanban-container {
        height: calc(100vh - 8rem);
        overflow-x: auto;
        overflow-y: hidden;
    }
</style>
