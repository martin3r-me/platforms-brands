<x-nx-modal size="md" model="modalShow" header="Board-Einstellungen">
    <form wire:submit="save">
        <div class="space-y-4">
            <div>
                <label for="boardName" class="block text-sm font-medium text-[var(--nx-text)] mb-1">Name *</label>
                <input type="text" id="boardName" wire:model="boardName" class="w-full rounded-lg border border-[var(--nx-line)] bg-[color:var(--nx-surface)] text-[var(--nx-text)] px-3 py-2 text-sm focus:border-[var(--nx-accent)] focus:ring-1 focus:ring-[var(--nx-accent)]">
                @error('boardName') <p class="text-xs text-[var(--nx-danger)] mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="boardDescription" class="block text-sm font-medium text-[var(--nx-text)] mb-1">Beschreibung</label>
                <textarea id="boardDescription" wire:model="boardDescription" rows="3" class="w-full rounded-lg border border-[var(--nx-line)] bg-[color:var(--nx-surface)] text-[var(--nx-text)] px-3 py-2 text-sm focus:border-[var(--nx-accent)] focus:ring-1 focus:ring-[var(--nx-accent)]" placeholder="Beschreibung des Persona Boards..."></textarea>
            </div>
        </div>
    </form>

    <x-slot name="footer">
        <x-nx-button variant="ghost" size="sm" type="button" wire:click="closeModal">
            Abbrechen
        </x-nx-button>
        <x-nx-button variant="primary" size="sm" wire:click="save">
            Speichern
        </x-nx-button>
    </x-slot>
</x-nx-modal>
