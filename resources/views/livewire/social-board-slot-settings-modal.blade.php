<x-nx-modal size="md" model="modalShow" header="Slot-Einstellungen">
    @if($slot)
        <div class="space-y-4">
            {{-- Slot Name --}}
            <x-nx-input-text
                name="slot.name"
                label="Slot-Name"
                wire:model.live.debounce.500ms="slot.name"
                placeholder="Slot-Name eingeben..."
                required
                :errorKey="'slot.name'"
            />
        </div>

        {{-- Slot löschen --}}
        <div class="mt-4">
            <x-nx-button variant="danger" size="sm" wire:click="deleteSlot" wire:confirm="Wirklich löschen? Alle Cards in diesem Slot werden ebenfalls gelöscht.">
                @svg('heroicon-o-trash', 'w-4 h-4') Slot löschen
            </x-nx-button>
        </div>
    @endif

    <x-slot name="footer">
        @if($slot)
            <x-nx-button variant="ghost" wire:click="closeModal">Abbrechen</x-nx-button>
            <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
        @endif
    </x-slot>
</x-nx-modal>
