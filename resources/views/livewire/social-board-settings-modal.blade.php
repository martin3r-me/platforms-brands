<x-nx-modal size="md" model="modalShow" header="Social Board-Einstellungen">
    @if($socialBoard)
        <div class="space-y-4">
            {{-- Social Board Name --}}
            @can('update', $socialBoard)
                <x-nx-input-text
                    name="socialBoard.name"
                    label="Social Board Name"
                    wire:model.live.debounce.500ms="socialBoard.name"
                    placeholder="Social Board Name eingeben..."
                    required
                    :errorKey="'socialBoard.name'"
                />
            @else
                <div class="flex items-center justify-between rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Social Board Name</span>
                    <span class="font-medium text-[color:var(--nx-text)]">{{ $socialBoard->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $socialBoard)
                <x-nx-input-textarea
                    name="socialBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="socialBoard.description"
                    placeholder="Beschreibung des Social Boards eingeben..."
                    :errorKey="'socialBoard.description'"
                />
            @else
                <div class="flex items-start justify-between gap-3 rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Beschreibung</span>
                    <span class="text-right font-medium text-[color:var(--nx-text)]">{{ $socialBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </div>

        {{-- Social Board löschen --}}
        @can('delete', $socialBoard)
            <div class="mt-4">
                <x-nx-button variant="danger" size="sm" wire:click="deleteSocialBoard" wire:confirm="Wirklich löschen?">
                    @svg('heroicon-o-trash', 'w-4 h-4') Social Board löschen
                </x-nx-button>
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($socialBoard)
            @can('update', $socialBoard)
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            @endcan
        @endif
    </x-slot>
</x-nx-modal>
