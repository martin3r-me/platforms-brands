<x-nx-modal size="md" model="modalShow" header="Guidelines Board Einstellungen">
    <div class="space-y-4">
        <x-nx-input-text
            name="boardName"
            label="Board-Name"
            wire:model.live.debounce.300ms="boardName"
            placeholder="z.B. Brand Guidelines, Markenregeln..."
            required
            :errorKey="'boardName'"
        />

        <x-nx-input-textarea
            name="boardDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="boardDescription"
            placeholder="Beschreibung des Guidelines Boards..."
            :errorKey="'boardDescription'"
        />
    </div>

    <x-slot name="footer">
        <x-nx-button variant="primary" wire:click="save">
            Speichern
        </x-nx-button>
    </x-slot>
</x-nx-modal>
