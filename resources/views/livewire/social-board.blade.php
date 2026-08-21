<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$socialBoard->name" icon="heroicon-o-share" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $socialBoard->brand->name, 'href' => route('brands.brands.show', $socialBoard->brand)],
            ['label' => $socialBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $socialBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-social-board-settings', { socialBoardId: {{ $socialBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
                <x-nx-button variant="ghost" size="sm" :href="route('brands.social-boards.editorial-plan', $socialBoard)" wire:navigate>
                    @svg('heroicon-o-calendar-days', 'w-4 h-4') Redaktionsplan
                </x-nx-button>
            </x-slot>

            @can('update', $socialBoard)
                <x-nx-button variant="primary" size="sm" wire:click="createSlot">
                    @svg('heroicon-o-plus', 'w-4 h-4') Slot erstellen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        @include('brands::partials.board-sidebar', ['detailRows' => [
            ['label' => 'Typ', 'value' => 'Social Board'],
            ['label' => 'Erstellt', 'value' => $socialBoard->created_at->format('d.m.Y')],
            ['label' => 'Slots', 'value' => (string) $slots->count()],
        ]])
    </x-slot>


    {{-- Board-Container: füllt restliche Breite, Spalten scrollen intern --}}
    @if($slots->count() > 0)
        <div class="social-board-kanban-container">
            <x-nx-kanban-container sortable="updateSlotOrder" sortable-group="updateCardOrder">
                @foreach($slots as $slot)
                    <x-nx-kanban-column :title="$slot->name" :sortable-id="$slot->id" :count="$slot->cards->count()" :scrollable="true">
                        <x-slot name="headerActions">
                            @can('update', $socialBoard)
                                <button
                                    wire:click="createCard({{ $slot->id }})"
                                    class="rounded-[6px] p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]"
                                    title="Neue Card"
                                >
                                    @svg('heroicon-o-plus-circle', 'w-4 h-4')
                                </button>
                                <button
                                    @click="$dispatch('open-modal-social-board-slot-settings', { slotId: {{ $slot->id }} })"
                                    class="rounded-[6px] p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]"
                                    title="Einstellungen"
                                >
                                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                </button>
                            @endcan
                        </x-slot>

                        @foreach($slot->cards as $card)
                            @include('brands::livewire.social-card-preview-card', ['card' => $card])
                        @endforeach
                    </x-nx-kanban-column>
                @endforeach
            </x-nx-kanban-container>
        </div>
    @else
        <div class="flex flex-1 items-center justify-center">
            <x-nx-empty icon="heroicon-o-view-columns">
                Noch keine Slots – erstelle deinen ersten Slot für dieses Social Board.
                @can('update', $socialBoard)
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
    <livewire:brands.social-board-settings-modal/>
    <livewire:brands.social-board-slot-settings-modal/>
</x-ui-page>
