<x-ui-modal size="lg" model="modalShow" header="{{ $entry ? 'Schrift-Definition bearbeiten' : 'Neue Schrift-Definition' }}">
    <div class="space-y-6">
        {{-- Self-hosted Katalog-Fonts für Live-Vorschau --}}
        @include('brands::partials.fonts')

        {{-- Name & Role --}}
        <x-ui-form-grid :cols="2" :gap="4">
            <x-ui-input-text
                name="entryName"
                label="Name"
                wire:model.live.debounce.300ms="entryName"
                placeholder="z.B. Headline 1, Body Text..."
                required
                :errorKey="'entryName'"
            />
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Rolle (Hierarchie)</label>
                <select wire:model.live="entryRole" class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                    <option value="">– Keine Rolle –</option>
                    @foreach(\Platform\Brands\Models\BrandsTypographyEntry::ROLES as $key => $label)
                        <option value="{{ $key }}">{{ $label }} ({{ strtoupper($key) }})</option>
                    @endforeach
                </select>
            </div>
        </x-ui-form-grid>

        {{-- Font Source Tabs --}}
        <div>
            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Schriftquelle</label>
            <div class="flex gap-1 p-1 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                <button
                    type="button"
                    wire:click="$set('fontSourceTab', 'catalog')"
                    class="flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors {{ $fontSourceTab === 'catalog' ? 'bg-white text-[var(--ui-primary)] shadow-sm' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                >
                    Katalog
                </button>
                <button
                    type="button"
                    wire:click="$set('fontSourceTab', 'custom')"
                    class="flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors {{ $fontSourceTab === 'custom' ? 'bg-white text-[var(--ui-primary)] shadow-sm' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                >
                    Eigene Schrift hochladen
                </button>
            </div>
        </div>

        {{-- Katalog: kuratierte, self-hosted Fonts (OFL) --}}
        @if($fontSourceTab === 'catalog')
            @php
                $catalog = collect(config('brands.fonts', []));
                $catLabels = config('brands.font_categories', []);
                $catFallbacks = config('brands.font_fallbacks', []);
            @endphp
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-[var(--ui-secondary)]">Schriftfamilie</label>
                    <span class="text-xs text-[var(--ui-muted)]">{{ $catalog->count() }} Schriften · OFL · self-hosted</span>
                </div>
                <div class="space-y-4 max-h-[340px] overflow-y-auto pr-1">
                    @foreach($catalog->groupBy('category') as $catKey => $fonts)
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-2">{{ $catLabels[$catKey] ?? $catKey }}</div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach($fonts as $f)
                                    @php $stack = "'" . $f['family'] . "', " . ($catFallbacks[$f['category']] ?? 'sans-serif'); @endphp
                                    <button
                                        type="button"
                                        wire:click="selectCatalogFont('{{ $f['key'] }}')"
                                        class="flex flex-col items-start gap-1 rounded-lg border px-3 py-2.5 text-left transition-colors {{ $entryFontFamily === $f['family'] ? 'border-[var(--ui-primary)] ring-1 ring-[var(--ui-primary)] bg-[var(--ui-primary-5)]' : 'border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)]' }}"
                                    >
                                        <span class="w-full truncate text-[19px] leading-tight text-[var(--ui-secondary)]" style="font-family: {{ $stack }};">{{ $f['label'] }}</span>
                                        <span class="text-[10.5px] text-[var(--ui-muted)]">{{ $f['label'] }}@if(!empty($f['family_group'])) · {{ $f['family_group'] }}@endif</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($entryFontFamily)
                    <p class="mt-2 text-xs text-[var(--ui-muted)]">Ausgewählt: <span class="font-medium text-[var(--ui-secondary)]">{{ $entryFontFamily }}</span></p>
                @endif
            </div>
        @endif

        {{-- Custom Font Upload --}}
        @if($fontSourceTab === 'custom')
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Schriftdatei hochladen</label>
                <div class="border-2 border-dashed border-[var(--ui-border)]/60 rounded-xl p-6 text-center bg-[var(--ui-muted-5)]">
                    <input
                        type="file"
                        wire:model="fontUpload"
                        accept=".woff2,.ttf,.otf,.woff"
                        class="hidden"
                        id="font-upload"
                    >
                    <label for="font-upload" class="cursor-pointer">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-50 mb-3">
                            @svg('heroicon-o-arrow-up-tray', 'w-6 h-6 text-purple-600')
                        </div>
                        <p class="text-sm font-medium text-[var(--ui-secondary)]">Klicke zum Hochladen</p>
                        <p class="text-xs text-[var(--ui-muted)] mt-1">WOFF2, TTF, OTF, WOFF (max. 10 MB)</p>
                    </label>
                </div>
                @if($fontUpload)
                    <div class="mt-2 flex items-center gap-2 p-2 bg-purple-50 rounded-lg border border-purple-200">
                        @svg('heroicon-o-document', 'w-4 h-4 text-purple-600')
                        <span class="text-sm text-purple-700">{{ $fontUpload->getClientOriginalName() }}</span>
                    </div>
                @endif
                @if($entry && $entry->font_source === 'custom' && $entry->font_file_name)
                    <div class="mt-2 flex items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                        @svg('heroicon-o-document-check', 'w-4 h-4 text-gray-600')
                        <span class="text-sm text-gray-700">Aktuell: {{ $entry->font_file_name }}</span>
                    </div>
                @endif
                @error('fontUpload')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Font Properties --}}
        <x-ui-form-grid :cols="2" :gap="4">
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Schriftgewicht</label>
                <select wire:model.live="entryFontWeight" class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                    @foreach(\Platform\Brands\Models\BrandsTypographyEntry::FONT_WEIGHTS as $weight => $label)
                        <option value="{{ $weight }}">{{ $label }} ({{ $weight }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Schriftstil</label>
                <select wire:model.live="entryFontStyle" class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                    <option value="normal">Normal</option>
                    <option value="italic">Kursiv (Italic)</option>
                </select>
            </div>
        </x-ui-form-grid>

        <x-ui-form-grid :cols="3" :gap="4">
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Schriftgröße (px)</label>
                <input
                    type="number"
                    wire:model.live.debounce.300ms="entryFontSize"
                    min="1"
                    max="999"
                    step="0.5"
                    class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Zeilenhöhe</label>
                <input
                    type="number"
                    wire:model.live.debounce.300ms="entryLineHeight"
                    min="0.5"
                    max="5"
                    step="0.1"
                    placeholder="z.B. 1.5"
                    class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Buchstabenabstand (px)</label>
                <input
                    type="number"
                    wire:model.live.debounce.300ms="entryLetterSpacing"
                    min="-5"
                    max="20"
                    step="0.1"
                    placeholder="z.B. 0.5"
                    class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                >
            </div>
        </x-ui-form-grid>

        <div>
            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Textumwandlung</label>
            <select wire:model.live="entryTextTransform" class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                <option value="">Keine</option>
                <option value="uppercase">GROSSBUCHSTABEN</option>
                <option value="lowercase">kleinbuchstaben</option>
                <option value="capitalize">Erster Buchstabe Groß</option>
            </select>
        </div>

        {{-- Sample Text --}}
        <x-ui-input-textarea
            name="entrySampleText"
            label="Beispieltext (für Vorschau)"
            wire:model.live.debounce.300ms="entrySampleText"
            placeholder="Der Text, der in der Vorschau angezeigt wird..."
            :errorKey="'entrySampleText'"
        />

        {{-- Description --}}
        <x-ui-input-textarea
            name="entryDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="entryDescription"
            placeholder="Hinweise zur Verwendung dieser Schrift-Definition..."
            :errorKey="'entryDescription'"
        />

        {{-- Live Preview --}}
        <div class="p-4 bg-[var(--ui-muted-5)] rounded-xl border border-[var(--ui-border)]/40">
            <div class="text-xs font-semibold text-[var(--ui-muted)] mb-3 uppercase tracking-wider">Live-Vorschau</div>
            @php
                $previewCf = collect(config('brands.fonts', []))->firstWhere('family', $entryFontFamily);
                $previewFb = config('brands.font_fallbacks', []);
                $previewStack = "'" . $entryFontFamily . "', " . ($previewCf ? ($previewFb[$previewCf['category']] ?? 'sans-serif') : 'sans-serif');
            @endphp
            <div
                style="font-family: {{ $previewStack }}; font-weight: {{ $entryFontWeight }}; font-style: {{ $entryFontStyle }}; font-size: {{ $entryFontSize }}px; {{ $entryLineHeight ? 'line-height: ' . $entryLineHeight . ';' : '' }} {{ $entryLetterSpacing !== null && $entryLetterSpacing !== '' ? 'letter-spacing: ' . $entryLetterSpacing . 'px;' : '' }} {{ $entryTextTransform ? 'text-transform: ' . $entryTextTransform . ';' : '' }}"
                class="text-[var(--ui-secondary)]"
            >
                {{ $entrySampleText ?: 'The quick brown fox jumps over the lazy dog' }}
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <x-ui-button variant="success" wire:click="save">
            {{ $entry ? 'Aktualisieren' : 'Erstellen' }}
        </x-ui-button>
    </x-slot>
</x-ui-modal>
