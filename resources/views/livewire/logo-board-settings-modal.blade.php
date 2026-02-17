<x-ui-modal size="md" model="modalShow" header="Logo Board-Einstellungen">
    @if($logoBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            @can('update', $logoBoard)
                <x-ui-input-text
                    name="logoBoard.name"
                    label="Board Name"
                    wire:model.live.debounce.500ms="logoBoard.name"
                    placeholder="Board Name eingeben..."
                    required
                    :errorKey="'logoBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">Board Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $logoBoard->name }}</span>
                </div>
            @endcan

            @can('update', $logoBoard)
                <x-ui-input-textarea
                    name="logoBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="logoBoard.description"
                    placeholder="Beschreibung des Logo Boards eingeben..."
                    :errorKey="'logoBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Beschreibung</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $logoBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>

        @can('delete', $logoBoard)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteLogoBoard" text="Logo Board löschen" confirmText="Wirklich löschen?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($logoBoard)
            @can('update', $logoBoard)
                <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
