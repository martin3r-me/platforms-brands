<x-ui-modal size="lg" model="modalShow" header="{{ $entry ? 'Schrift-Definition bearbeiten' : 'Neue Schrift-Definition' }}">
    <div class="space-y-6">
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
                    wire:click="$set('fontSourceTab', 'system')"
                    class="flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors {{ $fontSourceTab === 'system' ? 'bg-white text-[var(--ui-primary)] shadow-sm' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                >
                    System Fonts
                </button>
                <button
                    type="button"
                    wire:click="$set('fontSourceTab', 'google')"
                    class="flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors {{ $fontSourceTab === 'google' ? 'bg-white text-[var(--ui-primary)] shadow-sm' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                >
                    Google Fonts
                </button>
                <button
                    type="button"
                    wire:click="$set('fontSourceTab', 'custom')"
                    class="flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors {{ $fontSourceTab === 'custom' ? 'bg-white text-[var(--ui-primary)] shadow-sm' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                >
                    Custom Upload
                </button>
            </div>
        </div>

        {{-- System Font Selection --}}
        @if($fontSourceTab === 'system')
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Schriftfamilie</label>
                <select wire:model.live="entryFontFamily" class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                    <optgroup label="Sans-Serif">
                        <option value="Inter">Inter</option>
                        <option value="Arial">Arial</option>
                        <option value="Helvetica">Helvetica</option>
                        <option value="system-ui">System UI</option>
                        <option value="Segoe UI">Segoe UI</option>
                        <option value="Verdana">Verdana</option>
                        <option value="Tahoma">Tahoma</option>
                        <option value="Trebuchet MS">Trebuchet MS</option>
                    </optgroup>
                    <optgroup label="Serif">
                        <option value="Georgia">Georgia</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Palatino">Palatino</option>
                        <option value="Book Antiqua">Book Antiqua</option>
                    </optgroup>
                    <optgroup label="Monospace">
                        <option value="Consolas">Consolas</option>
                        <option value="Courier New">Courier New</option>
                        <option value="Monaco">Monaco</option>
                        <option value="Menlo">Menlo</option>
                    </optgroup>
                </select>
            </div>
        @endif

        {{-- Google Fonts Selection --}}
        @if($fontSourceTab === 'google')
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Google Font suchen</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="googleFontSearch"
                        wire:keyup="searchGoogleFonts"
                        placeholder="z.B. Roboto, Montserrat, Playfair..."
                        class="w-full px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                    >
                    @if(count($googleFontResults) > 0)
                        <div class="absolute z-10 w-full mt-1 bg-white border border-[var(--ui-border)] rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            @foreach($googleFontResults as $font)
                                <button
                                    type="button"
                                    wire:click="selectGoogleFont('{{ $font }}')"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-[var(--ui-muted-5)] transition-colors"
                                >
                                    {{ $font }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if($entryFontSource === 'google' && $entryFontFamily)
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-xs text-[var(--ui-muted)]">Ausgewählt:</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium border border-blue-200">
                            {{ $entryFontFamily }}
                        </span>
                    </div>
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
            @if($fontSourceTab === 'google' && $entryFontFamily)
                @php
                    $previewGoogleUrl = "https://fonts.googleapis.com/css2?family=" . str_replace(' ', '+', $entryFontFamily) . ":wght@" . $entryFontWeight . "&display=swap";
                @endphp
                <link href="{{ $previewGoogleUrl }}" rel="stylesheet">
            @endif
            <div
                style="font-family: '{{ $entryFontFamily }}', sans-serif; font-weight: {{ $entryFontWeight }}; font-style: {{ $entryFontStyle }}; font-size: {{ $entryFontSize }}px; {{ $entryLineHeight ? 'line-height: ' . $entryLineHeight . ';' : '' }} {{ $entryLetterSpacing !== null && $entryLetterSpacing !== '' ? 'letter-spacing: ' . $entryLetterSpacing . 'px;' : '' }} {{ $entryTextTransform ? 'text-transform: ' . $entryTextTransform . ';' : '' }}"
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
