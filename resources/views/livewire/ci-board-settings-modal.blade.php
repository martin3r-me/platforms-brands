<x-ui-modal size="md" model="modalShow" header="CI Board-Einstellungen">
    @if($ciBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- CI Board Name --}}
            @can('update', $ciBoard)
                <x-ui-input-text 
                    name="ciBoard.name"
                    label="CI Board Name"
                    wire:model.live.debounce.500ms="ciBoard.name"
                    placeholder="CI Board Name eingeben..."
                    required
                    :errorKey="'ciBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">CI Board Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $ciBoard->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $ciBoard)
                <x-ui-input-textarea 
                    name="ciBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="ciBoard.description"
                    placeholder="Beschreibung des CI Boards eingeben..."
                    :errorKey="'ciBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Beschreibung</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $ciBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>
        
        {{-- CI Board löschen --}}
        @can('delete', $ciBoard)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteCiBoard" text="CI Board löschen" confirmText="Wirklich löschen?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($ciBoard)
            @can('update', $ciBoard)
                <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
