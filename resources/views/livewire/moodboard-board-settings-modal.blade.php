<x-nx-modal size="md" model="modalShow" header="Moodboard Einstellungen">
    <div class="space-y-6">
        <x-nx-input-text
            name="boardName"
            label="Board-Name"
            wire:model.live.debounce.300ms="boardName"
            placeholder="z.B. Bildsprache, Moodboard, Visual Identity..."
            required
            :errorKey="'boardName'"
        />

        <x-nx-input-textarea
            name="boardDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="boardDescription"
            placeholder="Beschreibung des Moodboards..."
            :errorKey="'boardDescription'"
        />
    </div>

    <x-slot name="footer">
        <x-nx-button variant="primary" wire:click="save">
            Speichern
        </x-nx-button>
    </x-slot>
</x-nx-modal>
