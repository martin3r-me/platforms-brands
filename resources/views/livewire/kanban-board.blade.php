<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$kanbanBoard->name" icon="heroicon-o-view-columns" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $kanbanBoard->brand->name, 'href' => route('brands.brands.show', $kanbanBoard->brand)],
            ['label' => $kanbanBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $kanbanBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-kanban-board-settings', { kanbanBoardId: {{ $kanbanBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('extrafields:open')">
                        @svg('heroicon-o-adjustments-horizontal', 'w-4 h-4') Extra-Felder
                    </x-nx-button>
                @endcan
            </x-slot>

            @can('update', $kanbanBoard)
                <x-nx-button variant="primary" size="sm" wire:click="createSlot">
                    @svg('heroicon-o-plus', 'w-4 h-4') Slot erstellen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        @include('brands::partials.board-sidebar', ['detailRows' => [
            ['label' => 'Typ', 'value' => 'Kanban Board'],
            ['label' => 'Erstellt', 'value' => $kanbanBoard->created_at->format('d.m.Y')],
            ['label' => 'Slots', 'value' => (string) $slots->count()],
        ]])
    </x-slot>

    <x-slot name="activity">
        @include('brands::partials.board-activity')
    </x-slot>

    {{-- Board-Container: füllt restliche Breite, Spalten scrollen intern --}}
    @if($slots->count() > 0)
        <div class="kanban-board-kanban-container">
            <x-nx-kanban-container sortable="updateSlotOrder" sortable-group="updateCardOrder">
                @foreach($slots as $slot)
                    <x-nx-kanban-column :title="$slot->name" :sortable-id="$slot->id" :count="$slot->cards->count()" :scrollable="true">
                        <x-slot name="headerActions">
                            @can('update', $kanbanBoard)
                                <button
                                    wire:click="createCard({{ $slot->id }})"
                                    class="rounded-[6px] p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]"
                                    title="Neue Card"
                                >
                                    @svg('heroicon-o-plus-circle', 'w-4 h-4')
                                </button>
                                <button
                                    @click="$dispatch('open-modal-kanban-board-slot-settings', { slotId: {{ $slot->id }} })"
                                    class="rounded-[6px] p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]"
                                    title="Einstellungen"
                                >
                                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                </button>
                            @endcan
                        </x-slot>

                        @foreach($slot->cards as $card)
                            @include('brands::livewire.kanban-card-preview-card', ['card' => $card])
                        @endforeach
                    </x-nx-kanban-column>
                @endforeach
            </x-nx-kanban-container>
        </div>
    @else
        <div class="flex flex-1 items-center justify-center">
            <x-nx-empty icon="heroicon-o-view-columns">
                Noch keine Slots – erstelle deinen ersten Slot für dieses Kanban Board.
                @can('update', $kanbanBoard)
                    <x-slot name="action">
                        <x-nx-button variant="primary" size="sm" wire:click="createSlot">
                            @svg('heroicon-o-plus', 'w-4 h-4') Slot erstellen
                        </x-nx-button>
                    </x-slot>
                @endcan
            </x-nx-empty>
        </div>
    @endif

    {{-- Modals innerhalb des Page-Roots halten (ein Root-Element) --}}
    <livewire:brands.kanban-board-settings-modal/>
    <livewire:brands.kanban-board-slot-settings-modal/>
</x-ui-page>
