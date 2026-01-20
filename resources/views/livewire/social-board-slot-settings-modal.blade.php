<x-ui-modal size="md" model="modalShow" header="Slot-Einstellungen">
    @if($slot)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Slot Name --}}
            <x-ui-input-text 
                name="slot.name"
                label="Slot-Name"
                wire:model.live.debounce.500ms="slot.name"
                placeholder="Slot-Name eingeben..."
                required
                :errorKey="'slot.name'"
            />
        </x-ui-form-grid>
        
        {{-- Slot löschen --}}
        <div class="mt-4">
            <x-ui-confirm-button action="deleteSlot" text="Slot löschen" confirmText="Wirklich löschen? Alle Cards in diesem Slot werden ebenfalls gelöscht." />
        </div>
    @endif

    <x-slot name="footer">
        @if($slot)
            <x-ui-button variant="secondary" wire:click="closeModal">Abbrechen</x-ui-button>
            <x-ui-button variant="primary" wire:click="save">Speichern</x-ui-button>
        @endif
    </x-slot>
</x-ui-modal>
