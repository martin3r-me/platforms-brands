<x-nx-modal size="xl" model="modalShow" header="{{ $asset ? 'Asset bearbeiten' : 'Neues Asset' }}">
    <div class="space-y-6">
        {{-- File Upload --}}
        <div>
            <label class="block text-sm font-medium text-[color:var(--nx-text)] mb-2">
                Datei {{ $asset ? '(optional, neue Version hochladen)' : '(erforderlich)' }}
            </label>
            @if($asset && !$assetFile)
                <div class="mb-3 p-4 rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-hover)]">
                    <div class="flex items-center gap-3">
                        @if($asset->mime_type && str_starts_with($asset->mime_type, 'image/'))
                            <img src="{{ asset('storage/' . $asset->file_path) }}" alt="{{ $asset->name }}" class="w-16 h-16 object-cover rounded-lg">
                        @else
                            <div class="w-16 h-16 rounded-lg bg-gray-100 flex items-center justify-center">
                                @svg('heroicon-o-document', 'w-8 h-8 text-gray-400')
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-[color:var(--nx-text)]">{{ $asset->file_name }}</p>
                            <p class="text-xs text-[color:var(--nx-faint)]">
                                Version {{ $asset->current_version }}
                                @if($asset->file_size)
                                    &middot; {{ number_format($asset->file_size / 1024, 0, ',', '.') }} KB
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            @if($assetFile)
                <div class="mb-3 p-3 rounded-[6px] border border-green-200 bg-green-50/50">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-check-circle', 'w-5 h-5 text-green-600')
                        <span class="text-sm text-green-700">Neue Datei ausgewählt</span>
                    </div>
                </div>
            @endif
            <input type="file" wire:model="assetFile" class="block w-full text-sm text-[color:var(--nx-faint)] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 file:cursor-pointer">
            @error('assetFile') <p class="text-sm text-[color:var(--nx-danger)] mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Change Note (nur bei Update mit neuer Datei) --}}
        @if($asset && $assetFile)
            <x-nx-input-textarea
                name="changeNote"
                label="Änderungsnotiz (optional)"
                wire:model.live.debounce.300ms="changeNote"
                placeholder="Was hat sich geändert? z.B. Farben aktualisiert, neues Logo eingesetzt..."
                :errorKey="'changeNote'"
            />
        @endif

        {{-- Name --}}
        <x-nx-input-text
            name="assetName"
            label="Name"
            wire:model.live.debounce.300ms="assetName"
            placeholder="z.B. Instagram Story Template, E-Mail Signatur..."
            required
            :errorKey="'assetName'"
        />

        {{-- Description --}}
        <x-nx-input-textarea
            name="assetDescription"
            label="Beschreibung (optional)"
            wire:model.live.debounce.300ms="assetDescription"
            placeholder="Wofür wird dieses Asset verwendet?"
            :errorKey="'assetDescription'"
        />

        {{-- Asset Type --}}
        <div>
            <label class="block text-sm font-medium text-[color:var(--nx-text)] mb-2">Asset-Typ</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @foreach($availableTypes as $typeKey => $typeLabel)
                    <label
                        class="flex items-center gap-2 p-3 rounded-[6px] border-2 cursor-pointer transition-colors {{ $assetType === $typeKey ? 'border-sky-400 bg-sky-50/50' : 'border-[color:var(--nx-line)] hover:border-sky-200' }}"
                    >
                        <input type="radio" wire:model.live="assetType" value="{{ $typeKey }}" class="sr-only">
                        <span class="text-sm font-medium {{ $assetType === $typeKey ? 'text-sky-800' : 'text-[color:var(--nx-text)]' }}">{{ $typeLabel }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Kanal-Tags --}}
        <div>
            <label class="block text-sm font-medium text-[color:var(--nx-text)] mb-2">Kanal-Tags</label>
            <p class="text-xs text-[color:var(--nx-faint)] mb-3">Klicke auf Tags, um Kanäle zuzuweisen</p>
            <div class="flex flex-wrap gap-2">
                @foreach($availableTags as $tag)
                    <button
                        type="button"
                        wire:click="toggleTag('{{ $tag }}')"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-full border transition-colors {{ in_array($tag, $assetTags) ? 'bg-sky-500 text-white border-sky-500' : 'bg-white text-[color:var(--nx-text)] border-[color:var(--nx-line)] hover:border-sky-300 hover:bg-sky-50' }}"
                    >
                        {{ $tag }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Verfügbare Formate --}}
        <div>
            <label class="block text-sm font-medium text-[color:var(--nx-text)] mb-2">Verfügbare Formate</label>
            <p class="text-xs text-[color:var(--nx-faint)] mb-3">In welchen Formaten steht dieses Asset zur Verfügung?</p>
            <div class="flex flex-wrap gap-2">
                @foreach($availableFormats as $format)
                    <button
                        type="button"
                        wire:click="toggleFormat('{{ $format }}')"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-full border transition-colors uppercase {{ in_array($format, $assetFormats) ? 'bg-sky-500 text-white border-sky-500' : 'bg-white text-[color:var(--nx-text)] border-[color:var(--nx-line)] hover:border-sky-300 hover:bg-sky-50' }}"
                    >
                        {{ $format }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Versions-Historie (nur bei bestehendem Asset) --}}
        @if($asset && count($versions) > 0)
            <div>
                <label class="block text-sm font-medium text-[color:var(--nx-text)] mb-2">Versionen</label>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach($versions as $version)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-[color:var(--nx-line)] bg-[color:var(--nx-hover)]">
                            <div>
                                <span class="text-sm font-medium text-[color:var(--nx-text)]">v{{ $version->version_number }}</span>
                                @if($version->change_note)
                                    <span class="text-xs text-[color:var(--nx-faint)]"> – {{ $version->change_note }}</span>
                                @endif
                                <p class="text-xs text-[color:var(--nx-faint)]">{{ $version->created_at->format('d.m.Y H:i') }}</p>
                            </div>
                            <a href="{{ asset('storage/' . $version->file_path) }}" download="{{ $version->file_name }}" class="p-1.5 text-[color:var(--nx-faint)] hover:text-sky-600 transition-colors" title="Version {{ $version->version_number }} herunterladen">
                                @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <x-slot name="footer">
        <x-nx-button variant="primary" wire:click="save">
            {{ $asset ? 'Aktualisieren' : 'Erstellen' }}
        </x-nx-button>
    </x-slot>
</x-nx-modal>
