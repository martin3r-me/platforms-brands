<x-ui-modal size="md" model="modalShow" header="Tone of Voice Board Einstellungen">
    <div class="space-y-6">
        <x-ui-input-text
            name="boardName"
            label="Board-Name"
            wire:model.live.debounce.300ms="boardName"
            placeholder="z.B. Tone of Voice, Markenstimme..."
            required
            :errorKey="'boardName'"
        />

        <x-ui-input-textarea
            name="boardDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="boardDescription"
            placeholder="Beschreibung des Tone of Voice Boards..."
            :errorKey="'boardDescription'"
        />
    </div>

    <x-slot name="footer">
        <x-ui-button variant="success" wire:click="save">
            Speichern
        </x-ui-button>
    </x-slot>
</x-ui-modal>
