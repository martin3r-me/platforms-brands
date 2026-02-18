<x-ui-modal size="md" model="modalShow" header="SEO Board-Einstellungen">
    @if($seoBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- SEO Board Name --}}
            @can('update', $seoBoard)
                <x-ui-input-text
                    name="seoBoard.name"
                    label="SEO Board Name"
                    wire:model.live.debounce.500ms="seoBoard.name"
                    placeholder="SEO Board Name eingeben..."
                    required
                    :errorKey="'seoBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">SEO Board Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $seoBoard->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $seoBoard)
                <x-ui-input-textarea
                    name="seoBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="seoBoard.description"
                    placeholder="Beschreibung des SEO Boards eingeben..."
                    :errorKey="'seoBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Beschreibung</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $seoBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>

        {{-- SEO Board löschen --}}
        @can('delete', $seoBoard)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteSeoBoard" text="SEO Board löschen" confirmText="Wirklich löschen?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($seoBoard)
            @can('update', $seoBoard)
                <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
