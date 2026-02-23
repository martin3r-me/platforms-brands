<div>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="$parent.closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit="save">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-100 to-indigo-50 flex items-center justify-center">
                                @svg('heroicon-o-cube', 'w-5 h-5 text-indigo-600')
                            </div>
                            <h3 class="text-lg font-bold text-[var(--ui-secondary)]">
                                {{ $definitionId ? 'Definition bearbeiten' : 'Neue Definition' }}
                            </h3>
                        </div>

                        <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
                            {{-- Grunddaten --}}
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-[var(--ui-secondary)] border-b border-[var(--ui-border)]/40 pb-2">Grunddaten</h4>

                                <div>
                                    <label for="defName" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Name *</label>
                                    <input type="text" id="defName" wire:model="name" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="z.B. Firmenname, Branche, Zielgruppe...">
                                    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="defDescription" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Beschreibung</label>
                                    <textarea id="defDescription" wire:model="description" rows="2" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Kurze Beschreibung des Blocks..."></textarea>
                                </div>

                                <div>
                                    <label for="defBlockType" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Block-Typ *</label>
                                    <select id="defBlockType" wire:model="block_type" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach($blockTypes as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('block_type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="is_active" class="rounded border-[var(--ui-border)] text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-sm font-medium text-[var(--ui-secondary)]">Aktiv</span>
                                    </label>
                                    <p class="text-xs text-[var(--ui-muted)] mt-1 ml-6">Inaktive Definitionen koennen nicht zu neuen Boards hinzugefuegt werden</p>
                                </div>
                            </div>

                            {{-- KI-Konfiguration --}}
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-[var(--ui-secondary)] border-b border-[var(--ui-border)]/40 pb-2 flex items-center gap-2">
                                    @svg('heroicon-o-cpu-chip', 'w-4 h-4 text-indigo-500')
                                    KI-Prompt
                                </h4>

                                <div>
                                    <label for="defAiPrompt" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">AI-Prompt</label>
                                    <textarea id="defAiPrompt" wire:model="ai_prompt" rows="4" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono" placeholder="Beschreibe, wie die KI diese Frage stellen und interpretieren soll..."></textarea>
                                    <p class="text-xs text-[var(--ui-muted)] mt-1">Anweisungen fuer die KI, wie sie diesen Block im Gespraech behandeln soll</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[var(--ui-muted-5)] px-6 py-4 flex items-center justify-end gap-3 border-t border-[var(--ui-border)]/40">
                        <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="$parent.closeModal">
                            Abbrechen
                        </x-ui-button>
                        <x-ui-button variant="primary" size="sm" type="submit">
                            {{ $definitionId ? 'Speichern' : 'Erstellen' }}
                        </x-ui-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
