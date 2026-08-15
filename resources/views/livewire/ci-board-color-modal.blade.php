<x-nx-modal size="md" model="modalShow" :header="$isEdit ? 'Farbe bearbeiten' : 'Neue Farbe erstellen'">
    @if($color && $ciBoard)
        <div class="space-y-4">
            <x-nx-input-text name="color.title" label="Titel" wire:model.live.debounce.500ms="color.title" placeholder="z. B. Primärfarbe, Akzentfarbe…" required :errorKey="'color.title'" />

            <div>
                <label class="mb-1 block text-xs font-medium text-[color:var(--nx-text)]">Farbe</label>
                <div class="flex items-center gap-2">
                    <input type="color" wire:model.live="color.color" value="{{ $color->color ?: '#000000' }}"
                           class="h-9 w-12 shrink-0 cursor-pointer rounded-[6px] border border-[color:var(--nx-line-strong)] bg-transparent p-0.5">
                    <input type="text" wire:model.live="color.color" placeholder="#000000" pattern="^#[0-9A-Fa-f]{6}$"
                           class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-2.5 py-2 font-mono text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                </div>
                @error('color.color')<p class="mt-1 text-xs text-[color:var(--nx-danger)]">{{ $message }}</p>@enderror
            </div>

            <x-nx-input-textarea name="color.description" label="Beschreibung" :rows="3" wire:model.live.debounce.500ms="color.description" placeholder="Optionale Beschreibung der Farbe…" :errorKey="'color.description'" />
        </div>

        @if($isEdit)
            <div class="mt-4">
                <x-nx-button variant="danger" size="sm" wire:click="deleteColor" wire:confirm="Farbe wirklich löschen?">
                    @svg('heroicon-o-trash', 'w-4 h-4') Farbe löschen
                </x-nx-button>
            </div>
        @endif

        <x-slot name="footer">
            <x-nx-button variant="ghost" wire:click="closeModal">Abbrechen</x-nx-button>
            <x-nx-button variant="primary" wire:click="save">{{ $isEdit ? 'Aktualisieren' : 'Erstellen' }}</x-nx-button>
        </x-slot>
    @endif
</x-nx-modal>
