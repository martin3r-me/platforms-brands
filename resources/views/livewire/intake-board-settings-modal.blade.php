<div>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="$parent.showSettingsModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit="save">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-violet-100 to-violet-50 flex items-center justify-center">
                                @svg('heroicon-o-cog-6-tooth', 'w-5 h-5 text-violet-600')
                            </div>
                            <h3 class="text-lg font-bold text-[var(--ui-secondary)]">Board-Einstellungen</h3>
                        </div>

                        <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
                            {{-- Allgemein --}}
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-[var(--ui-secondary)] border-b border-[var(--ui-border)]/40 pb-2">Allgemein</h4>

                                <div>
                                    <label for="settingsName" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Name *</label>
                                    <input type="text" id="settingsName" wire:model="name" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-violet-500 focus:ring-violet-500">
                                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="settingsDescription" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Beschreibung</label>
                                    <textarea id="settingsDescription" wire:model="description" rows="3" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-violet-500 focus:ring-violet-500" placeholder="Beschreibung der Erhebung..."></textarea>
                                </div>
                            </div>

                            {{-- KI-Einstellungen --}}
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-[var(--ui-secondary)] border-b border-[var(--ui-border)]/40 pb-2 flex items-center gap-2">
                                    @svg('heroicon-o-cpu-chip', 'w-4 h-4 text-violet-500')
                                    KI-Konfiguration
                                </h4>

                                <div>
                                    <label for="settingsAiPersonality" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">KI-Persoenlichkeit</label>
                                    <textarea id="settingsAiPersonality" wire:model="ai_personality" rows="3" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-violet-500 focus:ring-violet-500" placeholder="z.B. Freundlich, professionell, branchenspezifisch..."></textarea>
                                    <p class="text-xs text-[var(--ui-muted)] mt-1">Beschreibt den Ton und Stil der KI-Interaktion mit Teilnehmern</p>
                                </div>

                                <div>
                                    <label for="settingsIndustryContext" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Branchen-Kontext</label>
                                    <textarea id="settingsIndustryContext" wire:model="industry_context" rows="3" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-violet-500 focus:ring-violet-500" placeholder="z.B. Gesundheitswesen, E-Commerce, B2B SaaS..."></textarea>
                                    <p class="text-xs text-[var(--ui-muted)] mt-1">Gibt der KI Kontext ueber die Branche, um relevantere Fragen zu stellen</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[var(--ui-muted-5)] px-6 py-4 flex items-center justify-end gap-3 border-t border-[var(--ui-border)]/40">
                        <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="$parent.showSettingsModal = false">
                            Abbrechen
                        </x-ui-button>
                        <x-ui-button variant="primary" size="sm" type="submit">
                            Speichern
                        </x-ui-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
