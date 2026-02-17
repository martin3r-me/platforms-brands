<x-ui-modal size="xl" model="modalShow" header="{{ $image ? 'Bild bearbeiten' : 'Neues Bild' }}">
    <div class="space-y-6">
        {{-- Image Upload (nur bei neuem Bild oder wenn ein neues Bild hochgeladen wird) --}}
        <div>
            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">
                Bild {{ $image ? '(optional, zum Ersetzen)' : '(erforderlich)' }}
            </label>
            @if($image && !$imageFile)
                <div class="mb-3 rounded-xl overflow-hidden border border-[var(--ui-border)]/60 max-w-xs">
                    <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $image->title }}" class="w-full block">
                </div>
            @endif
            @if($imageFile)
                <div class="mb-3 rounded-xl overflow-hidden border border-green-200 max-w-xs">
                    <img src="{{ $imageFile->temporaryUrl() }}" alt="Vorschau" class="w-full block">
                </div>
            @endif
            <input type="file" wire:model="imageFile" accept="image/*" class="block w-full text-sm text-[var(--ui-muted)] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100 file:cursor-pointer">
            @error('imageFile') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Title --}}
        <x-ui-input-text
            name="imageTitle"
            label="Titel (optional)"
            wire:model.live.debounce.300ms="imageTitle"
            placeholder="z.B. Sommerliche Farbpalette, Lifestyle-Shot..."
            :errorKey="'imageTitle'"
        />

        {{-- Annotation --}}
        <x-ui-input-textarea
            name="imageAnnotation"
            label="Annotation – Warum passt dieses Bild zur Marke?"
            wire:model.live.debounce.300ms="imageAnnotation"
            placeholder="Erkl&auml;re, warum dieses Bild die Bildsprache der Marke repr&auml;sentiert (oder warum nicht)..."
            :errorKey="'imageAnnotation'"
        />

        {{-- Type (Do/Don't) --}}
        <div>
            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Typ</label>
            <div class="grid grid-cols-2 gap-3">
                <label
                    class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors {{ $imageType === 'do' ? 'border-green-400 bg-green-50/50' : 'border-[var(--ui-border)]/60 hover:border-green-200' }}"
                >
                    <input type="radio" wire:model.live="imageType" value="do" class="sr-only">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $imageType === 'do' ? 'bg-green-500' : 'bg-green-100' }}">
                        @svg('heroicon-o-check', 'w-5 h-5 ' . ($imageType === 'do' ? 'text-white' : 'text-green-600'))
                    </span>
                    <div>
                        <span class="text-sm font-semibold {{ $imageType === 'do' ? 'text-green-800' : 'text-[var(--ui-secondary)]' }}">Passend (Do)</span>
                        <p class="text-xs text-[var(--ui-muted)]">Bild passt zur Marke</p>
                    </div>
                </label>
                <label
                    class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors {{ $imageType === 'dont' ? 'border-red-400 bg-red-50/50' : 'border-[var(--ui-border)]/60 hover:border-red-200' }}"
                >
                    <input type="radio" wire:model.live="imageType" value="dont" class="sr-only">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $imageType === 'dont' ? 'bg-red-500' : 'bg-red-100' }}">
                        @svg('heroicon-o-x-mark', 'w-5 h-5 ' . ($imageType === 'dont' ? 'text-white' : 'text-red-600'))
                    </span>
                    <div>
                        <span class="text-sm font-semibold {{ $imageType === 'dont' ? 'text-red-800' : 'text-[var(--ui-secondary)]' }}">Unpassend (Don't)</span>
                        <p class="text-xs text-[var(--ui-muted)]">Bild passt nicht</p>
                    </div>
                </label>
            </div>
        </div>

        {{-- Tags --}}
        <div>
            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Kategorien / Tags</label>
            <p class="text-xs text-[var(--ui-muted)] mb-3">Klicke auf Tags, um sie zuzuweisen</p>
            <div class="flex flex-wrap gap-2">
                @foreach($availableTags as $tag)
                    <button
                        type="button"
                        wire:click="toggleTag('{{ $tag }}')"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-full border transition-colors {{ in_array($tag, $imageTags) ? 'bg-rose-500 text-white border-rose-500' : 'bg-white text-[var(--ui-secondary)] border-[var(--ui-border)] hover:border-rose-300 hover:bg-rose-50' }}"
                    >
                        {{ $tag }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <x-ui-button variant="success" wire:click="save">
            {{ $image ? 'Aktualisieren' : 'Erstellen' }}
        </x-ui-button>
    </x-slot>
</x-ui-modal>
