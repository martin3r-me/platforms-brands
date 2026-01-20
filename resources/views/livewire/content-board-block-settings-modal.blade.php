<x-ui-modal size="md" model="modalShow" header="Block-Einstellungen">
    @if($block)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Block Name --}}
            <x-ui-input-text 
                name="name"
                label="Block Name"
                wire:model="name"
                placeholder="Block Name eingeben..."
                required
                :errorKey="'name'"
            />

            {{-- Beschreibung --}}
            <x-ui-input-textarea 
                name="description"
                label="Beschreibung"
                wire:model="description"
                placeholder="Beschreibung des Blocks eingeben..."
                :errorKey="'description'"
            />

            {{-- Span --}}
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">
                    Span (1-12)
                </label>
                <input 
                    type="number" 
                    min="1" 
                    max="12" 
                    wire:model="span"
                    class="w-full text-sm border border-[var(--ui-border)] rounded-lg px-3 py-2 focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent @error('span') border-red-500 @enderror"
                />
                @error('span')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @if($block)
                    @php
                        $row = $block->row;
                        $currentSum = $row->blocks->sum('span');
                        $currentBlockSpan = $block->span;
                        $newSum = $currentSum - $currentBlockSpan + ($span ?? $currentBlockSpan);
                    @endphp
                    <p class="mt-1 text-xs text-[var(--ui-muted)]">
                        Aktuell: {{ $currentSum }}/12 Spans in dieser Row
                        @if($span && $span != $currentBlockSpan)
                            <br>Mit neuem Wert: {{ $newSum }}/12 Spans
                        @endif
                    </p>
                @endif
            </div>
        </x-ui-form-grid>
        
        {{-- Block löschen --}}
        <div class="mt-4">
            <x-ui-confirm-button action="deleteBlock" text="Block löschen" confirmText="Wirklich löschen?" />
        </div>
    @endif

    <x-slot name="footer">
        @if($block)
            <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
        @endif
    </x-slot>
</x-ui-modal>
