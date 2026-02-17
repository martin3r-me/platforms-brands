<x-ui-modal size="lg" wire:model="modalShow">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-[var(--ui-secondary)] m-0">
                    {{ $competitor ? 'Wettbewerber bearbeiten' : 'Neuen Wettbewerber erstellen' }}
                </h2>
                <span class="text-xs text-[var(--ui-muted)] bg-[var(--ui-muted-5)] px-2 py-1 rounded-full">WETTBEWERBER</span>
            </div>
        </div>
    </x-slot>

    <form wire:submit="save">
        <div class="space-y-6">
            {{-- Grunddaten --}}
            <div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Grunddaten</h3>
                <div class="space-y-4">
                    <x-ui-input-text
                        name="competitorName"
                        label="Name *"
                        wire:model.live.debounce.500ms="competitorName"
                        placeholder="z.B. Wettbewerber GmbH"
                        :errorKey="'competitorName'"
                    />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui-input-text
                            name="competitorLogoUrl"
                            label="Logo URL"
                            wire:model.live.debounce.500ms="competitorLogoUrl"
                            placeholder="https://..."
                            :errorKey="'competitorLogoUrl'"
                        />
                        <x-ui-input-text
                            name="competitorWebsiteUrl"
                            label="Website"
                            wire:model.live.debounce.500ms="competitorWebsiteUrl"
                            placeholder="https://..."
                            :errorKey="'competitorWebsiteUrl'"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1.5">Beschreibung</label>
                        <textarea
                            wire:model.live.debounce.500ms="competitorDescription"
                            rows="3"
                            class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg bg-[var(--ui-surface)] text-[var(--ui-secondary)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                            placeholder="Kurze Beschreibung des Wettbewerbers..."
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2 p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                        <input
                            type="checkbox"
                            id="competitorIsOwnBrand"
                            wire:model="competitorIsOwnBrand"
                            class="w-4 h-4 text-[var(--ui-primary)] border-[var(--ui-border)] rounded focus:ring-[var(--ui-primary)]"
                        />
                        <label for="competitorIsOwnBrand" class="text-sm text-[var(--ui-secondary)]">
                            Dies ist die eigene Marke
                        </label>
                        <span class="text-xs text-[var(--ui-muted)] ml-auto">Für den Vergleich in der Differenzierungstabelle</span>
                    </div>
                </div>
            </div>

            {{-- Stärken --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-arrow-trending-up', 'w-5 h-5 text-green-500')
                        Stärken
                    </h3>
                    <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="addStrength">
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Hinzufügen
                        </div>
                    </x-ui-button>
                </div>
                @if(count($competitorStrengths) > 0)
                    <div class="space-y-2">
                        @foreach($competitorStrengths as $index => $strength)
                            <div class="flex items-center gap-2">
                                <x-ui-input-text
                                    name="competitorStrengths.{{ $index }}.text"
                                    wire:model.live.debounce.500ms="competitorStrengths.{{ $index }}.text"
                                    placeholder="Stärke..."
                                />
                                <button type="button" wire:click="removeStrength({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                        Noch keine Stärken hinzugefügt.
                    </div>
                @endif
            </div>

            {{-- Schwächen --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-arrow-trending-down', 'w-5 h-5 text-red-500')
                        Schwächen
                    </h3>
                    <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="addWeakness">
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Hinzufügen
                        </div>
                    </x-ui-button>
                </div>
                @if(count($competitorWeaknesses) > 0)
                    <div class="space-y-2">
                        @foreach($competitorWeaknesses as $index => $weakness)
                            <div class="flex items-center gap-2">
                                <x-ui-input-text
                                    name="competitorWeaknesses.{{ $index }}.text"
                                    wire:model.live.debounce.500ms="competitorWeaknesses.{{ $index }}.text"
                                    placeholder="Schwäche..."
                                />
                                <button type="button" wire:click="removeWeakness({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                        Noch keine Schwächen hinzugefügt.
                    </div>
                @endif
            </div>

            {{-- Notizen --}}
            <div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4 flex items-center gap-2">
                    @svg('heroicon-o-document-text', 'w-5 h-5 text-amber-500')
                    Notizen
                </h3>
                <textarea
                    wire:model.live.debounce.500ms="competitorNotes"
                    rows="3"
                    class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg bg-[var(--ui-surface)] text-[var(--ui-secondary)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                    placeholder="Freitext-Notizen zum Wettbewerber..."
                ></textarea>
            </div>

            {{-- Positionierung --}}
            <div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4 flex items-center gap-2">
                    @svg('heroicon-o-chart-bar-square', 'w-5 h-5 text-indigo-500')
                    Positionierung auf Matrix
                </h3>
                <div class="p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1.5">X-Position (0-100)</label>
                            <input type="range" wire:model.live="competitorPositionX" min="0" max="100" class="w-full accent-[var(--ui-primary)]">
                            <div class="flex justify-between text-[10px] text-[var(--ui-muted)] mt-1">
                                <span>0</span>
                                <span class="font-medium text-[var(--ui-primary)]">{{ $competitorPositionX }}</span>
                                <span>100</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1.5">Y-Position (0-100)</label>
                            <input type="range" wire:model.live="competitorPositionY" min="0" max="100" class="w-full accent-[var(--ui-primary)]">
                            <div class="flex justify-between text-[10px] text-[var(--ui-muted)] mt-1">
                                <span>0</span>
                                <span class="font-medium text-[var(--ui-primary)]">{{ $competitorPositionY }}</span>
                                <span>100</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Differenzierungsmerkmale --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-table-cells', 'w-5 h-5 text-emerald-500')
                        Differenzierungsmerkmale
                    </h3>
                    <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="addDifferentiation">
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Hinzufügen
                        </div>
                    </x-ui-button>
                </div>
                <p class="text-sm text-[var(--ui-muted)] mb-4">Vergleichsmerkmale für die Differenzierungstabelle.</p>
                @if(count($competitorDifferentiation) > 0)
                    <div class="space-y-3">
                        @foreach($competitorDifferentiation as $index => $diff)
                            <div class="p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-[var(--ui-muted)]">Merkmal {{ $index + 1 }}</span>
                                    <button type="button" wire:click="removeDifferentiation({{ $index }})" class="p-1 text-[var(--ui-muted)] hover:text-red-500 transition-colors">
                                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                                    </button>
                                </div>
                                <x-ui-input-text
                                    name="competitorDifferentiation.{{ $index }}.category"
                                    wire:model.live.debounce.500ms="competitorDifferentiation.{{ $index }}.category"
                                    placeholder="Kategorie (z.B. Preis, Service, Qualität)"
                                />
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <x-ui-input-text
                                        name="competitorDifferentiation.{{ $index }}.own_value"
                                        label="Eigene Marke"
                                        wire:model.live.debounce.500ms="competitorDifferentiation.{{ $index }}.own_value"
                                        placeholder="Eigene Marke"
                                    />
                                    <x-ui-input-text
                                        name="competitorDifferentiation.{{ $index }}.competitor_value"
                                        label="Wettbewerber"
                                        wire:model.live.debounce.500ms="competitorDifferentiation.{{ $index }}.competitor_value"
                                        placeholder="Wettbewerber"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                        Noch keine Differenzierungsmerkmale hinzugefügt.
                    </div>
                @endif
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button variant="secondary-outline" type="button" @click="modalShow = false">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" type="submit">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        {{ $competitor ? 'Speichern' : 'Erstellen' }}
                    </div>
                </x-ui-button>
            </div>
        </x-slot>
    </form>
</x-ui-modal>
