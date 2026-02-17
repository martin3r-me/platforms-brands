<x-ui-modal size="lg" model="modalShow" header="{{ $variant ? 'Logo-Variante bearbeiten' : 'Neue Logo-Variante' }}">
    <div class="space-y-6">
        {{-- Name & Type --}}
        <x-ui-form-grid :cols="2" :gap="4">
            <x-ui-input-text
                name="variantName"
                label="Name"
                wire:model.live.debounce.300ms="variantName"
                placeholder="z.B. Primary Logo, Favicon..."
                required
                :errorKey="'variantName'"
            />
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Typ</label>
                <select wire:model.live="variantType" class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                    @foreach(\Platform\Brands\Models\BrandsLogoVariant::TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </x-ui-form-grid>

        {{-- Logo Upload --}}
        <div>
            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Logo-Datei</label>
            <div class="border-2 border-dashed border-[var(--ui-border)]/60 rounded-xl p-6 text-center bg-[var(--ui-muted-5)]">
                <input
                    type="file"
                    wire:model="logoUpload"
                    accept=".svg,.png,.pdf,.jpg,.jpeg,.webp,.eps,.ai"
                    class="hidden"
                    id="logo-upload"
                >
                <label for="logo-upload" class="cursor-pointer">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-50 mb-3">
                        @svg('heroicon-o-arrow-up-tray', 'w-6 h-6 text-emerald-600')
                    </div>
                    <p class="text-sm font-medium text-[var(--ui-secondary)]">Klicke zum Hochladen</p>
                    <p class="text-xs text-[var(--ui-muted)] mt-1">SVG, PNG, PDF, JPG, WEBP, EPS, AI (max. 10 MB)</p>
                </label>
            </div>
            @if($logoUpload)
                <div class="mt-2 flex items-center gap-2 p-2 bg-emerald-50 rounded-lg border border-emerald-200">
                    @svg('heroicon-o-document', 'w-4 h-4 text-emerald-600')
                    <span class="text-sm text-emerald-700">{{ $logoUpload->getClientOriginalName() }}</span>
                </div>
            @endif
            @if($variant && $variant->file_name)
                <div class="mt-2 flex items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                    @svg('heroicon-o-document-check', 'w-4 h-4 text-gray-600')
                    <span class="text-sm text-gray-700">Aktuell: {{ $variant->file_name }} ({{ strtoupper($variant->file_format) }})</span>
                </div>
            @endif
            @error('logoUpload')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Background Color --}}
        <div>
            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Bevorzugte Hintergrundfarbe</label>
            <div class="flex items-center gap-3">
                <input
                    type="color"
                    wire:model.live="variantBackgroundColor"
                    class="w-10 h-10 p-0 border border-[var(--ui-border)] rounded-lg cursor-pointer"
                >
                <input
                    type="text"
                    wire:model.live.debounce.300ms="variantBackgroundColor"
                    placeholder="#FFFFFF"
                    class="flex-1 px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                >
            </div>
        </div>

        {{-- Protection Zone & Min Sizes --}}
        <div>
            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Schutzzonen & Mindestgrößen</label>
            <x-ui-form-grid :cols="3" :gap="4">
                <div>
                    <label class="block text-xs font-medium text-[var(--ui-muted)] mb-1">Schutzzone (Faktor)</label>
                    <input
                        type="number"
                        wire:model.live.debounce.300ms="variantClearspaceFactor"
                        min="0"
                        max="5"
                        step="0.1"
                        placeholder="z.B. 0.5"
                        class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                    >
                    <p class="text-[10px] text-[var(--ui-muted)] mt-1">Faktor der Logohöhe</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-[var(--ui-muted)] mb-1">Min. Breite (px)</label>
                    <input
                        type="number"
                        wire:model.live.debounce.300ms="variantMinWidthPx"
                        min="1"
                        max="9999"
                        placeholder="z.B. 120"
                        class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                    >
                    <p class="text-[10px] text-[var(--ui-muted)] mt-1">Für digitale Medien</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-[var(--ui-muted)] mb-1">Min. Breite (mm)</label>
                    <input
                        type="number"
                        wire:model.live.debounce.300ms="variantMinWidthMm"
                        min="1"
                        max="9999"
                        placeholder="z.B. 30"
                        class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                    >
                    <p class="text-[10px] text-[var(--ui-muted)] mt-1">Für Druckmedien</p>
                </div>
            </x-ui-form-grid>
        </div>

        {{-- Usage Guidelines --}}
        <x-ui-input-textarea
            name="variantUsageGuidelines"
            label="Verwendungsrichtlinien"
            wire:model.live.debounce.300ms="variantUsageGuidelines"
            placeholder="Wann und wie soll diese Logo-Variante verwendet werden..."
            :errorKey="'variantUsageGuidelines'"
        />

        {{-- Do's --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-[var(--ui-secondary)]">
                    <span class="inline-flex items-center gap-1.5">
                        @svg('heroicon-o-check-circle', 'w-4 h-4 text-emerald-500')
                        Do's (Richtige Verwendung)
                    </span>
                </label>
                <button type="button" wire:click="addDo" class="text-xs text-[var(--ui-primary)] hover:text-[var(--ui-primary-dark)] font-medium">+ Hinzufügen</button>
            </div>
            <div class="space-y-2">
                @foreach($dosList as $index => $do)
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            @svg('heroicon-o-check', 'w-3 h-3 text-emerald-600')
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="dosList.{{ $index }}.text"
                            placeholder="z.B. Logo immer mit Schutzzone verwenden"
                            class="flex-1 px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                        >
                        <button type="button" wire:click="removeDo({{ $index }})" class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                            @svg('heroicon-o-x-mark', 'w-4 h-4')
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Don'ts --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-[var(--ui-secondary)]">
                    <span class="inline-flex items-center gap-1.5">
                        @svg('heroicon-o-x-circle', 'w-4 h-4 text-red-500')
                        Don'ts (Falsche Verwendung)
                    </span>
                </label>
                <button type="button" wire:click="addDont" class="text-xs text-[var(--ui-primary)] hover:text-[var(--ui-primary-dark)] font-medium">+ Hinzufügen</button>
            </div>
            <div class="space-y-2">
                @foreach($dontsList as $index => $dont)
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            @svg('heroicon-o-x-mark', 'w-3 h-3 text-red-600')
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="dontsList.{{ $index }}.text"
                            placeholder="z.B. Logo nicht verzerren oder drehen"
                            class="flex-1 px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                        >
                        <button type="button" wire:click="removeDont({{ $index }})" class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                            @svg('heroicon-o-x-mark', 'w-4 h-4')
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Description --}}
        <x-ui-input-textarea
            name="variantDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="variantDescription"
            placeholder="Beschreibung dieser Logo-Variante..."
            :errorKey="'variantDescription'"
        />
    </div>

    <x-slot name="footer">
        <x-ui-button variant="success" wire:click="save">
            {{ $variant ? 'Aktualisieren' : 'Erstellen' }}
        </x-ui-button>
    </x-slot>
</x-ui-modal>
