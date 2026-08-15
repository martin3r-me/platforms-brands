<x-nx-modal size="md" model="modalShow" header="CI Board-Einstellungen">
    @if($ciBoard)
        <div class="space-y-4">
            @can('update', $ciBoard)
                <x-nx-input-text name="ciBoard.name" label="CI Board Name" wire:model.live.debounce.500ms="ciBoard.name" placeholder="Name eingeben…" required :errorKey="'ciBoard.name'" />
                <x-nx-input-textarea name="ciBoard.description" label="Beschreibung" :rows="3" wire:model.live.debounce.500ms="ciBoard.description" placeholder="Beschreibung eingeben…" :errorKey="'ciBoard.description'" />
            @else
                <div class="flex items-center justify-between rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Name</span><span class="font-medium text-[color:var(--nx-text)]">{{ $ciBoard->name }}</span>
                </div>
                <div class="flex items-start justify-between gap-3 rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Beschreibung</span><span class="text-right font-medium text-[color:var(--nx-text)]">{{ $ciBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </div>

        @can('delete', $ciBoard)
            <div class="mt-4">
                <x-nx-button variant="danger" size="sm" wire:click="deleteCiBoard" wire:confirm="CI Board wirklich löschen?">
                    @svg('heroicon-o-trash', 'w-4 h-4') CI Board löschen
                </x-nx-button>
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($ciBoard)
            @can('update', $ciBoard)
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            @endcan
        @endif
    </x-slot>
</x-nx-modal>
