<div>
    @if($modalShow)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit="save">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-100 to-orange-50 flex items-center justify-center">
                                    @svg('heroicon-o-building-office', 'w-5 h-5 text-orange-600')
                                </div>
                                <h3 class="text-lg font-bold text-[var(--ui-secondary)]">
                                    {{ $competitor ? 'Wettbewerber bearbeiten' : 'Neuen Wettbewerber erstellen' }}
                                </h3>
                            </div>

                            <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
                                {{-- Basic Info --}}
                                <div class="space-y-4">
                                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)] border-b border-[var(--ui-border)]/40 pb-2">Grunddaten</h4>

                                    <div>
                                        <label for="competitorName" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Name *</label>
                                        <input type="text" id="competitorName" wire:model="competitorName" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="z.B. Wettbewerber GmbH">
                                        @error('competitorName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="competitorLogoUrl" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Logo URL</label>
                                            <input type="url" id="competitorLogoUrl" wire:model="competitorLogoUrl" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="https://...">
                                        </div>
                                        <div>
                                            <label for="competitorWebsiteUrl" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Website</label>
                                            <input type="url" id="competitorWebsiteUrl" wire:model="competitorWebsiteUrl" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="https://...">
                                        </div>
                                    </div>

                                    <div>
                                        <label for="competitorDescription" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Beschreibung</label>
                                        <textarea id="competitorDescription" wire:model="competitorDescription" rows="3" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Kurze Beschreibung des Wettbewerbers..."></textarea>
                                    </div>

                                    <div class="flex items-center gap-3 px-3 py-2 bg-orange-50 rounded-lg border border-orange-100">
                                        <input type="checkbox" id="competitorIsOwnBrand" wire:model="competitorIsOwnBrand" class="rounded border-orange-300 text-orange-600 focus:ring-orange-500">
                                        <label for="competitorIsOwnBrand" class="text-sm font-medium text-orange-700">Dies ist die eigene Marke</label>
                                        <p class="text-xs text-orange-500 ml-auto">F&uuml;r den Vergleich in der Differenzierungstabelle</p>
                                    </div>
                                </div>

                                {{-- Strengths --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                                            @svg('heroicon-o-arrow-trending-up', 'w-4 h-4 text-green-500')
                                            St&auml;rken
                                        </h4>
                                        <button type="button" wire:click="addStrength" class="text-xs text-orange-600 hover:text-orange-700 font-medium">+ Hinzuf&uuml;gen</button>
                                    </div>
                                    @foreach($competitorStrengths as $index => $strength)
                                        <div class="flex items-center gap-2">
                                            <input type="text" wire:model="competitorStrengths.{{ $index }}.text" class="flex-1 rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="St&auml;rke...">
                                            <button type="button" wire:click="removeStrength({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Weaknesses --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                                            @svg('heroicon-o-arrow-trending-down', 'w-4 h-4 text-red-500')
                                            Schw&auml;chen
                                        </h4>
                                        <button type="button" wire:click="addWeakness" class="text-xs text-orange-600 hover:text-orange-700 font-medium">+ Hinzuf&uuml;gen</button>
                                    </div>
                                    @foreach($competitorWeaknesses as $index => $weakness)
                                        <div class="flex items-center gap-2">
                                            <input type="text" wire:model="competitorWeaknesses.{{ $index }}.text" class="flex-1 rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Schw&auml;che...">
                                            <button type="button" wire:click="removeWeakness({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Notes --}}
                                <div class="space-y-3">
                                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                                        @svg('heroicon-o-document-text', 'w-4 h-4 text-amber-500')
                                        Notizen
                                    </h4>
                                    <textarea wire:model="competitorNotes" rows="3" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Freitext-Notizen zum Wettbewerber..."></textarea>
                                </div>

                                {{-- Positioning --}}
                                <div class="space-y-3">
                                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)] border-b border-[var(--ui-border)]/40 pb-2 flex items-center gap-2">
                                        @svg('heroicon-o-chart-bar-square', 'w-4 h-4 text-indigo-500')
                                        Positionierung auf Matrix
                                    </h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="competitorPositionX" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">X-Position (0-100)</label>
                                            <input type="range" id="competitorPositionX" wire:model="competitorPositionX" min="0" max="100" class="w-full accent-indigo-600">
                                            <div class="flex justify-between text-[10px] text-[var(--ui-muted)]">
                                                <span>0</span>
                                                <span class="font-medium text-indigo-600">{{ $competitorPositionX }}</span>
                                                <span>100</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="competitorPositionY" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Y-Position (0-100)</label>
                                            <input type="range" id="competitorPositionY" wire:model="competitorPositionY" min="0" max="100" class="w-full accent-indigo-600">
                                            <div class="flex justify-between text-[10px] text-[var(--ui-muted)]">
                                                <span>0</span>
                                                <span class="font-medium text-indigo-600">{{ $competitorPositionY }}</span>
                                                <span>100</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Differentiation --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                                            @svg('heroicon-o-table-cells', 'w-4 h-4 text-emerald-500')
                                            Differenzierungsmerkmale
                                        </h4>
                                        <button type="button" wire:click="addDifferentiation" class="text-xs text-orange-600 hover:text-orange-700 font-medium">+ Hinzuf&uuml;gen</button>
                                    </div>
                                    <p class="text-xs text-[var(--ui-muted)]">Vergleichsmerkmale f&uuml;r die Differenzierungstabelle. &bdquo;Eigene Marke&ldquo;-Spalte wird nur bei der als eigene Marke markierten Eintr&auml;gen angezeigt.</p>
                                    @foreach($competitorDifferentiation as $index => $diff)
                                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-medium text-[var(--ui-muted)]">Merkmal {{ $index + 1 }}</span>
                                                <button type="button" wire:click="removeDifferentiation({{ $index }})" class="p-1 text-[var(--ui-muted)] hover:text-red-500 transition-colors">
                                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                                </button>
                                            </div>
                                            <input type="text" wire:model="competitorDifferentiation.{{ $index }}.category" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-1.5 text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Kategorie (z.B. Preis, Service, Qualit&auml;t)">
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" wire:model="competitorDifferentiation.{{ $index }}.own_value" class="rounded-lg border border-[var(--ui-border)] px-3 py-1.5 text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Eigene Marke">
                                                <input type="text" wire:model="competitorDifferentiation.{{ $index }}.competitor_value" class="rounded-lg border border-[var(--ui-border)] px-3 py-1.5 text-sm focus:border-orange-500 focus:ring-orange-500" placeholder="Wettbewerber">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="bg-[var(--ui-muted-5)] px-6 py-4 flex items-center justify-end gap-3 border-t border-[var(--ui-border)]/40">
                            <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="closeModal">
                                Abbrechen
                            </x-ui-button>
                            <x-ui-button variant="primary" size="sm" type="submit">
                                {{ $competitor ? 'Speichern' : 'Erstellen' }}
                            </x-ui-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
