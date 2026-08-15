<x-nx-modal size="md" model="modalShow" header="Logo Board-Einstellungen">
    @if($logoBoard)
        <div class="space-y-4">
            @can('update', $logoBoard)
                <x-nx-input-text
                    name="logoBoard.name"
                    label="Board Name"
                    wire:model.live.debounce.500ms="logoBoard.name"
                    placeholder="Board Name eingeben..."
                    required
                    :errorKey="'logoBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--nx-line)] bg-[color:var(--nx-surface)]">
                    <span class="text-[var(--nx-faint)]">Board Name</span>
                    <span class="font-medium text-[var(--nx-text)]">{{ $logoBoard->name }}</span>
                </div>
            @endcan

            @can('update', $logoBoard)
                <x-nx-input-textarea
                    name="logoBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="logoBoard.description"
                    placeholder="Beschreibung des Logo Boards eingeben..."
                    :errorKey="'logoBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--nx-line)] bg-[color:var(--nx-surface)]">
                    <span class="text-[var(--nx-faint)] mr-3">Beschreibung</span>
                    <span class="font-medium text-[var(--nx-text)] text-right">{{ $logoBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </div>

        @can('delete', $logoBoard)
            <div class="mt-4">
                <x-nx-button variant="danger" size="sm" wire:click="deleteLogoBoard" wire:confirm="Wirklich löschen?">
                    @svg('heroicon-o-trash', 'w-4 h-4') Logo Board löschen
                </x-nx-button>
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($logoBoard)
            @can('update', $logoBoard)
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            @endcan
        @endif
    </x-slot>
</x-nx-modal>
