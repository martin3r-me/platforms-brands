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
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-3">
                    Span (1-12)
                </label>
                <div class="grid grid-cols-6 gap-2">
                    @for($i = 1; $i <= 12; $i++)
                        <button
                            type="button"
                            wire:click="$set('span', {{ $i }})"
                            class="px-3 py-2 text-sm font-medium rounded-lg border transition-all
                                @if($span == $i)
                                    bg-[var(--ui-primary)] text-white border-[var(--ui-primary)] shadow-sm
                                @else
                                    bg-white text-[var(--ui-secondary)] border-[var(--ui-border)]/40 hover:border-[var(--ui-primary)]/60 hover:bg-[var(--ui-muted-5)]
                                @endif
                            "
                        >
                            {{ $i }}
                        </button>
                    @endfor
                </div>
                @error('span')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @if($block)
                    @php
                        $row = $block->row;
                        $currentSum = $row->blocks->sum('span');
                        $currentBlockSpan = $block->span;
                        $newSum = $currentSum - $currentBlockSpan + ($span ?? $currentBlockSpan);
                    @endphp
                    <p class="mt-3 text-xs text-[var(--ui-muted)]">
                        Aktuell: <span class="font-medium">{{ $currentSum }}/12</span> Spans in dieser Row
                        @if($span && $span != $currentBlockSpan)
                            <br>Mit neuem Wert: <span class="font-medium {{ $newSum > 12 ? 'text-red-600' : '' }}">{{ $newSum }}/12</span> Spans
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
