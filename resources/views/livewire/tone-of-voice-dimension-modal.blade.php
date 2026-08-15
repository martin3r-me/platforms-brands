<x-nx-modal size="md" model="modalShow" header="{{ $dimension ? 'Tone-Dimension bearbeiten' : 'Neue Tone-Dimension' }}">
    <div class="space-y-4">
        {{-- Name --}}
        <x-nx-input-text
            name="dimensionName"
            label="Name der Dimension"
            wire:model.live.debounce.300ms="dimensionName"
            placeholder="z.B. Formalität, Humor, Komplexität..."
            required
            :errorKey="'dimensionName'"
        />

        {{-- Labels --}}
        <div class="grid grid-cols-2 gap-4">
            <x-nx-input-text
                name="dimensionLabelLeft"
                label="Linkes Label"
                wire:model.live.debounce.300ms="dimensionLabelLeft"
                placeholder="z.B. Formell, Ernst, Technisch..."
                required
                :errorKey="'dimensionLabelLeft'"
            />
            <x-nx-input-text
                name="dimensionLabelRight"
                label="Rechtes Label"
                wire:model.live.debounce.300ms="dimensionLabelRight"
                placeholder="z.B. Locker, Humorvoll, Einfach..."
                required
                :errorKey="'dimensionLabelRight'"
            />
        </div>

        {{-- Slider Value --}}
        <div>
            <label class="block text-sm font-medium text-[color:var(--nx-text)] mb-2">Position auf der Skala</label>
            <div class="flex items-center gap-4" x-data="{ sliderValue: @entangle('dimensionValue') }">
                <span class="text-xs font-medium text-[color:var(--nx-accent)] bg-[color:var(--nx-accent-soft)] px-2 py-1 rounded-md min-w-[80px] text-center">{{ $dimensionLabelLeft ?: 'Links' }}</span>
                <div class="flex-1">
                    <input
                        type="range"
                        min="0"
                        max="100"
                        x-model="sliderValue"
                        class="w-full h-2 rounded-full appearance-none cursor-pointer bg-[color:var(--nx-accent-soft)]
                               [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-[color:var(--nx-accent)] [&::-webkit-slider-thumb]:cursor-pointer
                               [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-[color:var(--nx-accent)] [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:cursor-pointer"
                    >
                    <div class="flex justify-between text-[10px] text-[color:var(--nx-faint)] mt-1">
                        <span>0</span>
                        <span x-text="sliderValue" class="font-semibold text-[color:var(--nx-accent)]"></span>
                        <span>100</span>
                    </div>
                </div>
                <span class="text-xs font-medium text-[color:var(--nx-accent)] bg-[color:var(--nx-accent-soft)] px-2 py-1 rounded-md min-w-[80px] text-center">{{ $dimensionLabelRight ?: 'Rechts' }}</span>
            </div>
        </div>

        {{-- Description --}}
        <x-nx-input-textarea
            name="dimensionDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="dimensionDescription"
            placeholder="Erläuterung dieser Dimension..."
            :errorKey="'dimensionDescription'"
        />
    </div>

    <x-slot name="footer">
        <x-nx-button variant="primary" wire:click="save">
            {{ $dimension ? 'Aktualisieren' : 'Erstellen' }}
        </x-nx-button>
    </x-slot>
</x-nx-modal>
