<x-nx-modal size="md" model="modalShow" header="Typografie Board-Einstellungen">
    @if($typographyBoard)
        <div class="space-y-4">
            @can('update', $typographyBoard)
                <x-nx-input-text
                    name="typographyBoard.name"
                    label="Board Name"
                    wire:model.live.debounce.500ms="typographyBoard.name"
                    placeholder="Board Name eingeben..."
                    required
                    :errorKey="'typographyBoard.name'"
                />
            @else
                <div class="flex items-center justify-between rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Board Name</span>
                    <span class="font-medium text-[color:var(--nx-text)]">{{ $typographyBoard->name }}</span>
                </div>
            @endcan

            @can('update', $typographyBoard)
                <x-nx-input-textarea
                    name="typographyBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="typographyBoard.description"
                    placeholder="Beschreibung des Typografie Boards eingeben..."
                    :errorKey="'typographyBoard.description'"
                />
            @else
                <div class="flex items-start justify-between gap-3 rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Beschreibung</span>
                    <span class="text-right font-medium text-[color:var(--nx-text)]">{{ $typographyBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </div>

        @can('delete', $typographyBoard)
            <div class="mt-4">
                <x-nx-button variant="danger" size="sm" wire:click="deleteTypographyBoard" wire:confirm="Wirklich löschen?">
                    @svg('heroicon-o-trash', 'w-4 h-4') Typografie Board löschen
                </x-nx-button>
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($typographyBoard)
            @can('update', $typographyBoard)
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            @endcan
        @endif
    </x-slot>
</x-nx-modal>
