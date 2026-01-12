<x-ui-modal size="md" model="modalShow" header="{{ $isEdit ? 'Farbe bearbeiten' : 'Neue Farbe erstellen' }}">
    @if($color && $ciBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            <div>
                <x-ui-input-text
                    name="color.title"
                    label="Titel"
                    wire:model.live.debounce.500ms="color.title"
                    placeholder="z.B. Primärfarbe, Akzentfarbe..."
                    required
                    :errorKey="'color.title'"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Farbe</label>
                <div class="flex items-center gap-3">
                    <input type="color" 
                           wire:model.live="color.color" 
                           value="{{ $color->color ?? '#000000' }}"
                           class="w-16 h-10 rounded border border-[var(--ui-border)] cursor-pointer">
                    <input type="text" 
                           wire:model.live="color.color" 
                           value="{{ $color->color ?? '' }}"
                           placeholder="#000000"
                           pattern="^#[0-9A-Fa-f]{6}$"
                           class="flex-1 px-3 py-2 border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                </div>
                @error('color.color')
                    <p class="mt-1 text-sm text-[var(--ui-danger)]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-ui-input-textarea
                    name="color.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="color.description"
                    placeholder="Optionale Beschreibung der Farbe..."
                    rows="3"
                    :errorKey="'color.description'"
                />
            </div>
        </x-ui-form-grid>

        @if($isEdit)
            <div class="mt-4">
                <x-ui-confirm-button 
                    action="deleteColor" 
                    text="Farbe löschen" 
                    confirmText="Wirklich löschen?" 
                    variant="danger"
                />
            </div>
        @endif

        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-ui-button variant="secondary-outline" size="sm" wire:click="closeModal">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="success" size="sm" wire:click="save">
                    {{ $isEdit ? 'Aktualisieren' : 'Erstellen' }}
                </x-ui-button>
            </div>
        </x-slot>
    @endif
</x-ui-modal>
