<x-ui-modal size="md" model="modalShow" header="Typografie Board-Einstellungen">
    @if($typographyBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            @can('update', $typographyBoard)
                <x-ui-input-text
                    name="typographyBoard.name"
                    label="Board Name"
                    wire:model.live.debounce.500ms="typographyBoard.name"
                    placeholder="Board Name eingeben..."
                    required
                    :errorKey="'typographyBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">Board Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $typographyBoard->name }}</span>
                </div>
            @endcan

            @can('update', $typographyBoard)
                <x-ui-input-textarea
                    name="typographyBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="typographyBoard.description"
                    placeholder="Beschreibung des Typografie Boards eingeben..."
                    :errorKey="'typographyBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Beschreibung</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $typographyBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>

        @can('delete', $typographyBoard)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteTypographyBoard" text="Typografie Board löschen" confirmText="Wirklich löschen?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($typographyBoard)
            @can('update', $typographyBoard)
                <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
