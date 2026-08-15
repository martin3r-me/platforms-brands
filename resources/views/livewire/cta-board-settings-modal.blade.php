<x-nx-modal size="md" model="modalShow" header="CTA Board-Einstellungen">
    @if($ctaBoard)
        <div class="space-y-4">
            {{-- CTA Board Name --}}
            @can('update', $ctaBoard)
                <x-nx-input-text
                    name="ctaBoard.name"
                    label="CTA Board Name"
                    wire:model.live.debounce.500ms="ctaBoard.name"
                    placeholder="CTA Board Name eingeben..."
                    required
                    :errorKey="'ctaBoard.name'"
                />
            @else
                <div class="flex items-center justify-between rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">CTA Board Name</span>
                    <span class="font-medium text-[color:var(--nx-text)]">{{ $ctaBoard->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $ctaBoard)
                <x-nx-input-textarea
                    name="ctaBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="ctaBoard.description"
                    placeholder="Beschreibung des CTA Boards eingeben..."
                    :errorKey="'ctaBoard.description'"
                />
            @else
                <div class="flex items-start justify-between gap-3 rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Beschreibung</span>
                    <span class="text-right font-medium text-[color:var(--nx-text)]">{{ $ctaBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </div>

        {{-- CTA Board löschen --}}
        @can('delete', $ctaBoard)
            <div class="mt-4">
                <x-nx-button variant="danger" size="sm" wire:click="deleteCtaBoard" wire:confirm="Wirklich löschen?">
                    @svg('heroicon-o-trash', 'w-4 h-4') CTA Board löschen
                </x-nx-button>
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($ctaBoard)
            @can('update', $ctaBoard)
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            @endcan
        @endif
    </x-slot>
</x-nx-modal>
