<x-ui-modal size="md" model="modalShow" header="Guidelines Board Einstellungen">
    <div class="space-y-6">
        <x-ui-input-text
            name="boardName"
            label="Board-Name"
            wire:model.live.debounce.300ms="boardName"
            placeholder="z.B. Brand Guidelines, Markenregeln..."
            required
            :errorKey="'boardName'"
        />

        <x-ui-input-textarea
            name="boardDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="boardDescription"
            placeholder="Beschreibung des Guidelines Boards..."
            :errorKey="'boardDescription'"
        />
    </div>

    <x-slot name="footer">
        <x-ui-button variant="success" wire:click="save">
            Speichern
        </x-ui-button>
    </x-slot>
</x-ui-modal>
