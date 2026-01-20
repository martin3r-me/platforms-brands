<x-ui-modal size="md" model="modalShow" header="Social Board-Einstellungen">
    @if($socialBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Social Board Name --}}
            @can('update', $socialBoard)
                <x-ui-input-text 
                    name="socialBoard.name"
                    label="Social Board Name"
                    wire:model.live.debounce.500ms="socialBoard.name"
                    placeholder="Social Board Name eingeben..."
                    required
                    :errorKey="'socialBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">Social Board Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $socialBoard->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $socialBoard)
                <x-ui-input-textarea 
                    name="socialBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="socialBoard.description"
                    placeholder="Beschreibung des Social Boards eingeben..."
                    :errorKey="'socialBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Beschreibung</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $socialBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>
        
        {{-- Social Board löschen --}}
        @can('delete', $socialBoard)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteSocialBoard" text="Social Board löschen" confirmText="Wirklich löschen?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($socialBoard)
            @can('update', $socialBoard)
                <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
