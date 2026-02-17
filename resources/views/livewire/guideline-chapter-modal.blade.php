<x-ui-modal size="md" model="modalShow" header="{{ $chapter ? 'Kapitel bearbeiten' : 'Neues Kapitel' }}">
    <div class="space-y-6">
        <x-ui-input-text
            name="chapterTitle"
            label="Kapitel-Titel"
            wire:model.live.debounce.300ms="chapterTitle"
            placeholder="z.B. Logo-Verwendung, Farbrichtlinien..."
            required
            :errorKey="'chapterTitle'"
        />

        <x-ui-input-textarea
            name="chapterDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="chapterDescription"
            placeholder="Kurze Beschreibung des Kapitels..."
            :errorKey="'chapterDescription'"
        />

        <x-ui-input-text
            name="chapterIcon"
            label="Icon (optional)"
            wire:model.live.debounce.300ms="chapterIcon"
            placeholder="z.B. heroicon-o-paint-brush"
            :errorKey="'chapterIcon'"
        />
    </div>

    <x-slot name="footer">
        <x-ui-button variant="success" wire:click="save">
            {{ $chapter ? 'Aktualisieren' : 'Erstellen' }}
        </x-ui-button>
    </x-slot>
</x-ui-modal>
