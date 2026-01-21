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

            {{-- Content Type --}}
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-3">
                    Content-Typ
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        wire:click="setContentType('text')"
                        class="px-4 py-2 text-sm font-medium rounded-lg border transition-all
                            @if($contentType === 'text')
                                bg-[var(--ui-primary)] text-white border-[var(--ui-primary)] shadow-sm
                            @else
                                bg-white text-[var(--ui-secondary)] border-[var(--ui-border)]/40 hover:border-[var(--ui-primary)]/60 hover:bg-[var(--ui-muted-5)]
                            @endif
                        "
                    >
                        Text
                    </button>
                    <button
                        type="button"
                        disabled
                        class="px-4 py-2 text-sm font-medium rounded-lg border bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed"
                        title="Bald verfügbar"
                    >
                        Bild
                    </button>
                    <button
                        type="button"
                        disabled
                        class="px-4 py-2 text-sm font-medium rounded-lg border bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed"
                        title="Bald verfügbar"
                    >
                        Karussell
                    </button>
                    <button
                        type="button"
                        disabled
                        class="px-4 py-2 text-sm font-medium rounded-lg border bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed"
                        title="Bald verfügbar"
                    >
                        Video
                    </button>
                </div>
                @if($contentType)
                    <p class="mt-2 text-xs text-[var(--ui-muted)]">
                        Aktueller Typ: <span class="font-medium">{{ $contentType }}</span>
                    </p>
                @else
                    <p class="mt-2 text-xs text-[var(--ui-muted)]">
                        Noch kein Content-Typ ausgewählt
                    </p>
                @endif
            </div>

            {{-- Span --}}
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-3">
                    Span (1-12)
                </label>
                @if($block)
                    @php
                        $row = $block->row;
                        $row->load('blocks');
                        $currentSum = $row->blocks->sum('span');
                        $currentBlockSpan = $block->span;
                        // Verfügbare Spans berechnen: 12 - (aktuelle Summe - aktueller Block-Span)
                        $availableSpan = 12 - ($currentSum - $currentBlockSpan);
                        // Maximal verfügbarer Wert ist 12
                        $maxAvailable = min(12, $availableSpan);
                    @endphp
                    <div class="grid grid-cols-6 gap-2">
                        @for($i = 1; $i <= $maxAvailable; $i++)
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
                    <p class="mt-3 text-xs text-[var(--ui-muted)]">
                        Verfügbar: <span class="font-medium">{{ $availableSpan }}/12</span> Spans
                        @if($span && $span != $currentBlockSpan)
                            @php
                                $newSum = $currentSum - $currentBlockSpan + $span;
                            @endphp
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
