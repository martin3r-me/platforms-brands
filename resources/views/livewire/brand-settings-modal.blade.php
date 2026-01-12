<x-ui-modal size="md" model="modalShow" header="Marken-Einstellungen">
    @if($brand)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Marken Name --}}
            @can('update', $brand)
                <x-ui-input-text 
                    name="brand.name"
                    label="Markenname"
                    wire:model.live.debounce.500ms="brand.name"
                    placeholder="Markenname eingeben..."
                    required
                    :errorKey="'brand.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">Markenname</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $brand->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $brand)
                <x-ui-input-textarea 
                    name="brand.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="brand.description"
                    placeholder="Beschreibung der Marke eingeben..."
                    :errorKey="'brand.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Beschreibung</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $brand->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>
        
        {{-- Marke abschließen --}}
        @can('update', $brand)
            @if(!$brand->done)
                <div class="border-t pt-4 mt-4">
                    <x-ui-button 
                        variant="success" 
                        wire:click="markAsDone"
                        class="w-full"
                    >
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-check-circle','w-5 h-5')
                            <span>Marke abschließen</span>
                        </span>
                    </x-ui-button>
                </div>
            @else
                <div class="border-t pt-4 mt-4">
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center gap-2 text-green-700">
                            @svg('heroicon-o-check-circle','w-5 h-5')
                            <span class="font-medium">Marke abgeschlossen</span>
                        </div>
                        @if($brand->done_at)
                            <p class="text-sm text-green-600 mt-1">
                                Abgeschlossen am: {{ $brand->done_at->format('d.m.Y H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        @endcan
        
        {{-- Marke löschen --}}
        @can('delete', $brand)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteBrand" text="Marke löschen" confirmText="Wirklich löschen?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($brand)
            @can('update', $brand)
                <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
