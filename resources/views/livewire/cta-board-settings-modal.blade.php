<x-ui-modal size="md" model="modalShow" header="CTA Board-Einstellungen">
    @if($ctaBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- CTA Board Name --}}
            @can('update', $ctaBoard)
                <x-ui-input-text
                    name="ctaBoard.name"
                    label="CTA Board Name"
                    wire:model.live.debounce.500ms="ctaBoard.name"
                    placeholder="CTA Board Name eingeben..."
                    required
                    :errorKey="'ctaBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">CTA Board Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $ctaBoard->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $ctaBoard)
                <x-ui-input-textarea
                    name="ctaBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="ctaBoard.description"
                    placeholder="Beschreibung des CTA Boards eingeben..."
                    :errorKey="'ctaBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Beschreibung</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $ctaBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>

        {{-- CTA Board löschen --}}
        @can('delete', $ctaBoard)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteCtaBoard" text="CTA Board löschen" confirmText="Wirklich löschen?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($ctaBoard)
            @can('update', $ctaBoard)
                <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
