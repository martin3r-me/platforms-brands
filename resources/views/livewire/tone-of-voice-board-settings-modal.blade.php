<x-nx-modal size="md" model="modalShow" header="Tone of Voice Board Einstellungen">
    <div class="space-y-4">
        <x-nx-input-text
            name="boardName"
            label="Board-Name"
            wire:model.live.debounce.300ms="boardName"
            placeholder="z.B. Tone of Voice, Markenstimme..."
            required
            :errorKey="'boardName'"
        />

        <x-nx-input-textarea
            name="boardDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="boardDescription"
            placeholder="Beschreibung des Tone of Voice Boards..."
            :errorKey="'boardDescription'"
        />
    </div>

    <x-slot name="footer">
        <x-nx-button variant="primary" wire:click="save">
            Speichern
        </x-nx-button>
    </x-slot>
</x-nx-modal>
