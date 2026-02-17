<div>
    @if($modalShow)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="save">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-100 to-teal-50 flex items-center justify-center">
                                    @svg('heroicon-o-cog-6-tooth', 'w-5 h-5 text-teal-600')
                                </div>
                                <h3 class="text-lg font-bold text-[var(--ui-secondary)]">Board-Einstellungen</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label for="boardName" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Name *</label>
                                    <input type="text" id="boardName" wire:model="boardName" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
                                    @error('boardName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="boardDescription" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Beschreibung</label>
                                    <textarea id="boardDescription" wire:model="boardDescription" rows="3" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Beschreibung des Persona Boards..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-[var(--ui-muted-5)] px-6 py-4 flex items-center justify-end gap-3 border-t border-[var(--ui-border)]/40">
                            <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="closeModal">
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
    @endif
</div>
