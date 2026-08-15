<x-nx-modal size="lg" model="modalShow" header="{{ $entry ? 'Schrift-Definition bearbeiten' : 'Neue Schrift-Definition' }}">
    <div class="space-y-6">
        {{-- Self-hosted Katalog-Fonts für Live-Vorschau --}}
        @include('brands::partials.fonts')

        {{-- Name & Role --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-nx-input-text
                name="entryName"
                label="Name"
                wire:model.live.debounce.300ms="entryName"
                placeholder="z.B. Headline 1, Body Text..."
                required
                :errorKey="'entryName'"
            />
            <div>
                <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Rolle (Hierarchie)</label>
                <select wire:model.live="entryRole" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                    <option value="">– Keine Rolle –</option>
                    @foreach(\Platform\Brands\Models\BrandsTypographyEntry::ROLES as $key => $label)
                        <option value="{{ $key }}">{{ $label }} ({{ strtoupper($key) }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Font Source Tabs --}}
        <div>
            <label class="mb-2 block text-sm font-medium text-[color:var(--nx-text)]">Schriftquelle</label>
            <div class="flex gap-1 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-hover)] p-1">
                <button
                    type="button"
                    wire:click="$set('fontSourceTab', 'catalog')"
                    class="flex-1 rounded-[6px] px-3 py-2 text-sm font-medium transition-colors {{ $fontSourceTab === 'catalog' ? 'bg-[color:var(--nx-surface)] text-[color:var(--nx-accent)]' : 'text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]' }}"
                >
                    Katalog
                </button>
                <button
                    type="button"
                    wire:click="$set('fontSourceTab', 'custom')"
                    class="flex-1 rounded-[6px] px-3 py-2 text-sm font-medium transition-colors {{ $fontSourceTab === 'custom' ? 'bg-[color:var(--nx-surface)] text-[color:var(--nx-accent)]' : 'text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]' }}"
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
                $pickerSpecimen = trim((string) $entrySampleText) !== '' ? $entrySampleText : 'Marken mit klarem Charakter — AaGg 0123';
            @endphp
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <label class="block text-sm font-medium text-[color:var(--nx-text)]">Schriftfamilie</label>
                    <span class="text-xs text-[color:var(--nx-faint)]">{{ $catalog->count() }} Schriften · OFL · self-hosted</span>
                </div>
                <div class="max-h-[380px] space-y-4 overflow-y-auto pr-1">
                    @foreach($catalog->groupBy('category') as $catKey => $fonts)
                        <div>
                            <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">{{ $catLabels[$catKey] ?? $catKey }}</div>
                            <div class="space-y-1.5">
                                @foreach($fonts as $f)
                                    @php $stack = "'" . $f['family'] . "', " . ($catFallbacks[$f['category']] ?? 'sans-serif'); @endphp
                                    <button
                                        type="button"
                                        wire:click="selectCatalogFont('{{ $f['key'] }}')"
                                        class="block w-full rounded-[8px] border px-3.5 py-2.5 text-left transition-colors {{ $entryFontFamily === $f['family'] ? 'border-[color:var(--nx-accent)] ring-1 ring-[color:var(--nx-accent)] bg-[color:var(--nx-accent-soft)]' : 'border-[color:var(--nx-line)] hover:bg-[color:var(--nx-hover)]' }}"
                                    >
                                        <div class="flex items-baseline justify-between gap-3">
                                            <span class="text-[11px] font-medium text-[color:var(--nx-text)]">{{ $f['label'] }}@if(!empty($f['family_group']))<span class="font-normal text-[color:var(--nx-faint)]"> · {{ $f['family_group'] }}</span>@endif</span>
                                            @if($entryFontFamily === $f['family'])<span class="text-[10px] font-medium text-[color:var(--nx-accent)]">Ausgewählt</span>@endif
                                        </div>
                                        <div class="mt-1 truncate text-[color:var(--nx-text)]" style="font-family: {{ $stack }}; font-size: 23px; line-height: 1.3;">{{ $pickerSpecimen }}</div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($entryFontFamily)
                    <p class="mt-2 text-xs text-[color:var(--nx-faint)]">Ausgewählt: <span class="font-medium text-[color:var(--nx-text)]">{{ $entryFontFamily }}</span></p>
                @endif
            </div>
        @endif

        {{-- Custom Font Upload --}}
        @if($fontSourceTab === 'custom')
            <div>
                <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Schriftdatei hochladen</label>
                <div class="rounded-[10px] border-2 border-dashed border-[color:var(--nx-line-strong)] bg-[color:var(--nx-hover)] p-6 text-center">
                    <input
                        type="file"
                        wire:model="fontUpload"
                        accept=".woff2,.ttf,.otf,.woff"
                        class="hidden"
                        id="font-upload"
                    >
                    <label for="font-upload" class="cursor-pointer">
                        <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)]">
                            @svg('heroicon-o-arrow-up-tray', 'w-6 h-6 text-[color:var(--nx-accent)]')
                        </div>
                        <p class="text-sm font-medium text-[color:var(--nx-text)]">Klicke zum Hochladen</p>
                        <p class="mt-1 text-xs text-[color:var(--nx-faint)]">WOFF2, TTF, OTF, WOFF (max. 10 MB)</p>
                    </label>
                </div>
                @if($fontUpload)
                    <div class="mt-2 flex items-center gap-2 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-accent-soft)] p-2">
                        @svg('heroicon-o-document', 'w-4 h-4 text-[color:var(--nx-accent)]')
                        <span class="text-sm text-[color:var(--nx-text)]">{{ $fontUpload->getClientOriginalName() }}</span>
                    </div>
                @endif
                @if($entry && $entry->font_source === 'custom' && $entry->font_file_name)
                    <div class="mt-2 flex items-center gap-2 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-hover)] p-2">
                        @svg('heroicon-o-document-check', 'w-4 h-4 text-[color:var(--nx-faint)]')
                        <span class="text-sm text-[color:var(--nx-text)]">Aktuell: {{ $entry->font_file_name }}</span>
                    </div>
                @endif
                @error('fontUpload')
                    <p class="mt-1 text-xs text-[color:var(--nx-danger)]">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Font Properties --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Schriftgewicht</label>
                <select wire:model.live="entryFontWeight" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                    @foreach(\Platform\Brands\Models\BrandsTypographyEntry::FONT_WEIGHTS as $weight => $label)
                        <option value="{{ $weight }}">{{ $label }} ({{ $weight }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Schriftstil</label>
                <select wire:model.live="entryFontStyle" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                    <option value="normal">Normal</option>
                    <option value="italic">Kursiv (Italic)</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Schriftgröße (px)</label>
                <input
                    type="number"
                    wire:model.live.debounce.300ms="entryFontSize"
                    min="1"
                    max="999"
                    step="0.5"
                    class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Zeilenhöhe</label>
                <input
                    type="number"
                    wire:model.live.debounce.300ms="entryLineHeight"
                    min="0.5"
                    max="5"
                    step="0.1"
                    placeholder="z.B. 1.5"
                    class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Buchstabenabstand (px)</label>
                <input
                    type="number"
                    wire:model.live.debounce.300ms="entryLetterSpacing"
                    min="-5"
                    max="20"
                    step="0.1"
                    placeholder="z.B. 0.5"
                    class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]"
                >
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-[color:var(--nx-text)]">Textumwandlung</label>
            <select wire:model.live="entryTextTransform" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                <option value="">Keine</option>
                <option value="uppercase">GROSSBUCHSTABEN</option>
                <option value="lowercase">kleinbuchstaben</option>
                <option value="capitalize">Erster Buchstabe Groß</option>
            </select>
        </div>

        {{-- Sample Text --}}
        <x-nx-input-textarea
            name="entrySampleText"
            label="Beispieltext (für Vorschau)"
            wire:model.live.debounce.300ms="entrySampleText"
            placeholder="Der Text, der in der Vorschau angezeigt wird..."
            :errorKey="'entrySampleText'"
        />

        {{-- Description --}}
        <x-nx-input-textarea
            name="entryDescription"
            label="Beschreibung"
            wire:model.live.debounce.300ms="entryDescription"
            placeholder="Hinweise zur Verwendung dieser Schrift-Definition..."
            :errorKey="'entryDescription'"
        />

        {{-- Live Preview --}}
        <div class="rounded-[10px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-4">
            <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">Live-Vorschau</div>
            @php
                $previewCf = collect(config('brands.fonts', []))->firstWhere('family', $entryFontFamily);
                $previewFb = config('brands.font_fallbacks', []);
                $previewStack = "'" . $entryFontFamily . "', " . ($previewCf ? ($previewFb[$previewCf['category']] ?? 'sans-serif') : 'sans-serif');
            @endphp
            <div
                style="font-family: {{ $previewStack }}; font-weight: {{ $entryFontWeight }}; font-style: {{ $entryFontStyle }}; font-size: {{ $entryFontSize }}px; {{ $entryLineHeight ? 'line-height: ' . $entryLineHeight . ';' : '' }} {{ $entryLetterSpacing !== null && $entryLetterSpacing !== '' ? 'letter-spacing: ' . $entryLetterSpacing . 'px;' : '' }} {{ $entryTextTransform ? 'text-transform: ' . $entryTextTransform . ';' : '' }}"
                class="text-[color:var(--nx-text)]"
            >
                {{ $entrySampleText ?: 'The quick brown fox jumps over the lazy dog' }}
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <x-nx-button variant="primary" wire:click="save">
            {{ $entry ? 'Aktualisieren' : 'Erstellen' }}
        </x-nx-button>
    </x-slot>
</x-nx-modal>
