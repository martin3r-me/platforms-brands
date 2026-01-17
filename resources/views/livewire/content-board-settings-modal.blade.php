<x-ui-modal size="md" model="modalShow" header="Content Board-Einstellungen">
    @if($contentBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Content Board Name --}}
            @can('update', $contentBoard)
                <x-ui-input-text 
                    name="contentBoard.name"
                    label="Content Board Name"
                    wire:model.live.debounce.500ms="contentBoard.name"
                    placeholder="Content Board Name eingeben..."
                    required
                    :errorKey="'contentBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">Content Board Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $contentBoard->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $contentBoard)
                <x-ui-input-textarea 
                    name="contentBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="contentBoard.description"
                    placeholder="Beschreibung des Content Boards eingeben..."
                    :errorKey="'contentBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Beschreibung</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $contentBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>
        
        {{-- Content Board löschen --}}
        @can('delete', $contentBoard)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteContentBoard" text="Content Board löschen" confirmText="Wirklich löschen?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($contentBoard)
            @can('update', $contentBoard)
                <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
