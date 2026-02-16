<x-ui-modal size="md" model="modalShow" header="{{ $dimension ? 'Tone-Dimension bearbeiten' : 'Neue Tone-Dimension' }}">
    <div class="space-y-6">
        {{-- Name --}}
        <x-ui-input-text
            name="dimensionName"
            label="Name der Dimension"
            wire:model.live.debounce.300ms="dimensionName"
            placeholder="z.B. Formalit&auml;t, Humor, Komplexit&auml;t..."
            required
            :errorKey="'dimensionName'"
        />

        {{-- Labels --}}
        <x-ui-form-grid :cols="2" :gap="4">
            <x-ui-input-text
                name="dimensionLabelLeft"
                label="Linkes Label"
                wire:model.live.debounce.300ms="dimensionLabelLeft"
                placeholder="z.B. Formell, Ernst, Technisch..."
                required
                :errorKey="'dimensionLabelLeft'"
            />
            <x-ui-input-text
                name="dimensionLabelRight"
                label="Rechtes Label"
                wire:model.live.debounce.300ms="dimensionLabelRight"
                placeholder="z.B. Locker, Humorvoll, Einfach..."
                required
                :errorKey="'dimensionLabelRight'"
            />
        </x-ui-form-grid>

        {{-- Slider Value --}}
        <div>
            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Position auf der Skala</label>
            <div class="flex items-center gap-4" x-data="{ sliderValue: @entangle('dimensionValue') }">
                <span class="text-xs font-medium text-violet-700 bg-violet-50 px-2 py-1 rounded-md border border-violet-200 min-w-[80px] text-center">{{ $dimensionLabelLeft ?: 'Links' }}</span>
                <div class="flex-1">
                    <input
                        type="range"
                        min="0"
                        max="100"
                        x-model="sliderValue"
                        class="w-full h-2 rounded-full appearance-none cursor-pointer bg-gradient-to-r from-violet-300 to-violet-100"
                    >
                    <div class="flex justify-between text-[10px] text-[var(--ui-muted)] mt-1">
                        <span>0</span>
                        <span x-text="sliderValue" class="font-semibold text-violet-600"></span>
                        <span>100</span>
                    </div>
                </div>
                <span class="text-xs font-medium text-violet-700 bg-violet-50 px-2 py-1 rounded-md border border-violet-200 min-w-[80px] text-center">{{ $dimensionLabelRight ?: 'Rechts' }}</span>
            </div>
        </div>

        {{-- Description --}}
        <x-ui-input-textarea
            name="dimensionDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="dimensionDescription"
            placeholder="Erl&auml;uterung dieser Dimension..."
            :errorKey="'dimensionDescription'"
        />
    </div>

    <x-slot name="footer">
        <x-ui-button variant="success" wire:click="save">
            {{ $dimension ? 'Aktualisieren' : 'Erstellen' }}
        </x-ui-button>
    </x-slot>
</x-ui-modal>
