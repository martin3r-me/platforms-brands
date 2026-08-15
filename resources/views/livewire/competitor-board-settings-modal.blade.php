<x-nx-modal size="md" model="modalShow" header="Board-Einstellungen">
    <form wire:submit="save" id="competitor-board-settings-form">
        <div class="space-y-6">
            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-[color:var(--nx-text)] border-b border-[color:var(--nx-line)] pb-2">Allgemein</h4>
                <div>
                    <label for="boardName" class="block text-sm font-medium text-[color:var(--nx-text)] mb-1">Name *</label>
                    <input type="text" id="boardName" wire:model="boardName" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-2 text-sm focus:border-[color:var(--nx-accent)] focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                    @error('boardName') <p class="text-xs text-[color:var(--nx-danger)] mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="boardDescription" class="block text-sm font-medium text-[color:var(--nx-text)] mb-1">Beschreibung</label>
                    <textarea id="boardDescription" wire:model="boardDescription" rows="3" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-2 text-sm focus:border-[color:var(--nx-accent)] focus:ring-1 focus:ring-[color:var(--nx-accent)]" placeholder="Beschreibung des Wettbewerber Boards..."></textarea>
                </div>
            </div>

            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-[color:var(--nx-text)] border-b border-[color:var(--nx-line)] pb-2 flex items-center gap-2">
                    @svg('heroicon-o-chart-bar-square', 'w-4 h-4 text-[color:var(--nx-accent)]')
                    Positionierungsmatrix-Achsen
                </h4>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="axisXLabel" class="block text-sm font-medium text-[color:var(--nx-text)] mb-1">X-Achse *</label>
                        <input type="text" id="axisXLabel" wire:model="axisXLabel" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-2 text-sm focus:border-[color:var(--nx-accent)] focus:ring-1 focus:ring-[color:var(--nx-accent)]" placeholder="z.B. Preis">
                        @error('axisXLabel') <p class="text-xs text-[color:var(--nx-danger)] mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="axisYLabel" class="block text-sm font-medium text-[color:var(--nx-text)] mb-1">Y-Achse *</label>
                        <input type="text" id="axisYLabel" wire:model="axisYLabel" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-2 text-sm focus:border-[color:var(--nx-accent)] focus:ring-1 focus:ring-[color:var(--nx-accent)]" placeholder="z.B. Qualität">
                        @error('axisYLabel') <p class="text-xs text-[color:var(--nx-danger)] mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="axisXMinLabel" class="block text-sm font-medium text-[color:var(--nx-text)] mb-1">X-Min</label>
                        <input type="text" id="axisXMinLabel" wire:model="axisXMinLabel" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-2 text-sm focus:border-[color:var(--nx-accent)] focus:ring-1 focus:ring-[color:var(--nx-accent)]" placeholder="z.B. Niedrig">
                    </div>
                    <div>
                        <label for="axisXMaxLabel" class="block text-sm font-medium text-[color:var(--nx-text)] mb-1">X-Max</label>
                        <input type="text" id="axisXMaxLabel" wire:model="axisXMaxLabel" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-2 text-sm focus:border-[color:var(--nx-accent)] focus:ring-1 focus:ring-[color:var(--nx-accent)]" placeholder="z.B. Hoch">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="axisYMinLabel" class="block text-sm font-medium text-[color:var(--nx-text)] mb-1">Y-Min</label>
                        <input type="text" id="axisYMinLabel" wire:model="axisYMinLabel" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-2 text-sm focus:border-[color:var(--nx-accent)] focus:ring-1 focus:ring-[color:var(--nx-accent)]" placeholder="z.B. Niedrig">
                    </div>
                    <div>
                        <label for="axisYMaxLabel" class="block text-sm font-medium text-[color:var(--nx-text)] mb-1">Y-Max</label>
                        <input type="text" id="axisYMaxLabel" wire:model="axisYMaxLabel" class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-2 text-sm focus:border-[color:var(--nx-accent)] focus:ring-1 focus:ring-[color:var(--nx-accent)]" placeholder="z.B. Hoch">
                    </div>
                </div>
            </div>
        </div>
    </form>

    <x-slot name="footer">
        <x-nx-button variant="ghost" size="sm" type="button" wire:click="closeModal">
            Abbrechen
        </x-nx-button>
        <x-nx-button variant="primary" size="sm" type="submit" form="competitor-board-settings-form">
            Speichern
        </x-nx-button>
    </x-slot>
</x-nx-modal>
