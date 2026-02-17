<x-ui-modal size="xl" model="modalShow" header="{{ $entry ? 'Regel bearbeiten' : 'Neue Regel' }}">
    <div class="space-y-6">
        {{-- Title --}}
        <x-ui-input-text
            name="entryTitle"
            label="Regel-Titel"
            wire:model.live.debounce.300ms="entryTitle"
            placeholder="z.B. Mindestgröße des Logos, Farbverwendung..."
            required
            :errorKey="'entryTitle'"
        />

        {{-- Rule Text --}}
        <x-ui-input-textarea
            name="entryRuleText"
            label="Regel-Text"
            wire:model.live.debounce.300ms="entryRuleText"
            placeholder="Die eigentliche Markenregel / Richtlinie..."
            required
            :errorKey="'entryRuleText'"
        />

        {{-- Rationale --}}
        <x-ui-input-textarea
            name="entryRationale"
            label="Begründung (optional)"
            wire:model.live.debounce.300ms="entryRationale"
            placeholder="Warum existiert diese Regel? Welches Problem wird gelöst?"
            :errorKey="'entryRationale'"
        />

        {{-- Do/Don't Examples --}}
        <div class="border border-[var(--ui-border)]/60 rounded-xl overflow-hidden">
            <div class="bg-[var(--ui-muted-5)] px-4 py-3 border-b border-[var(--ui-border)]/40">
                <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">Do / Don't Beispiele</h3>
                <p class="text-xs text-[var(--ui-muted)]">Zeige anhand von Beispielen, was richtig und falsch ist</p>
            </div>
            <div class="p-4 space-y-4">
                {{-- Do Example --}}
                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-[var(--ui-secondary)] mb-1">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100">
                            @svg('heroicon-o-check', 'w-3 h-3 text-green-600')
                        </span>
                        Do (positives Beispiel)
                    </label>
                    <textarea
                        wire:model.live.debounce.300ms="entryDoExample"
                        placeholder="Beschreibe, wie es richtig gemacht wird..."
                        rows="3"
                        class="w-full px-3 py-2 text-sm border border-green-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent bg-green-50/50"
                    ></textarea>
                </div>

                {{-- Don't Example --}}
                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-[var(--ui-secondary)] mb-1">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-100">
                            @svg('heroicon-o-x-mark', 'w-3 h-3 text-red-600')
                        </span>
                        Don't (negatives Beispiel)
                    </label>
                    <textarea
                        wire:model.live.debounce.300ms="entryDontExample"
                        placeholder="Beschreibe, wie es NICHT gemacht werden soll..."
                        rows="3"
                        class="w-full px-3 py-2 text-sm border border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent bg-red-50/50"
                    ></textarea>
                </div>
            </div>
        </div>

        {{-- Cross References --}}
        <div class="border border-[var(--ui-border)]/60 rounded-xl overflow-hidden">
            <div class="bg-[var(--ui-muted-5)] px-4 py-3 border-b border-[var(--ui-border)]/40 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">Cross-Referenzen</h3>
                    <p class="text-xs text-[var(--ui-muted)]">Verlinke andere Boards (CI-Farben, Logos, Typografie...)</p>
                </div>
                <button
                    type="button"
                    wire:click="addCrossReference"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-cyan-700 bg-cyan-50 border border-cyan-200 rounded-md hover:bg-cyan-100 transition-colors"
                >
                    @svg('heroicon-o-plus', 'w-3 h-3')
                    Referenz
                </button>
            </div>
            <div class="p-4 space-y-3">
                @forelse($entryCrossReferences as $index => $ref)
                    <div class="flex items-center gap-2">
                        <select
                            wire:model.live="entryCrossReferences.{{ $index }}.board_type"
                            class="flex-1 px-3 py-2 text-sm border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                        >
                            <option value="">Board-Typ wählen...</option>
                            @foreach($availableBoards as $board)
                                <option value="{{ $board['board_type'] }}___{{ $board['board_id'] }}">{{ $board['label'] }}</option>
                            @endforeach
                        </select>
                        <x-ui-input-text
                            name="entryCrossReferences.{{ $index }}.label"
                            wire:model.live.debounce.300ms="entryCrossReferences.{{ $index }}.label"
                            placeholder="Label..."
                            class="flex-1"
                        />
                        <button
                            type="button"
                            wire:click="removeCrossReference({{ $index }})"
                            class="p-2 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                        >
                            @svg('heroicon-o-trash', 'w-4 h-4')
                        </button>
                    </div>
                @empty
                    <p class="text-xs text-[var(--ui-muted)] text-center py-2">Keine Cross-Referenzen hinzugefügt</p>
                @endforelse
            </div>
        </div>

        {{-- Live Preview --}}
        @if($entryRuleText)
            <div class="p-4 bg-[var(--ui-muted-5)] rounded-xl border border-[var(--ui-border)]/40">
                <div class="text-xs font-semibold text-[var(--ui-muted)] mb-3 uppercase tracking-wider">Vorschau</div>
                <h4 class="text-base font-semibold text-[var(--ui-secondary)] mb-2">{{ $entryTitle ?: 'Neue Regel' }}</h4>
                <p class="text-sm text-[var(--ui-secondary)] leading-relaxed mb-3">{{ $entryRuleText }}</p>
                @if($entryDoExample || $entryDontExample)
                    <div class="grid grid-cols-2 gap-3">
                        @if($entryDoExample)
                            <div class="p-3 rounded-lg border-2 border-green-200 bg-green-50/50">
                                <div class="flex items-center gap-1 mb-1">
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-green-500">
                                        @svg('heroicon-o-check', 'w-2.5 h-2.5 text-white')
                                    </span>
                                    <span class="text-[10px] font-bold text-green-800 uppercase">Do</span>
                                </div>
                                <p class="text-xs text-green-900">{{ Str::limit($entryDoExample, 100) }}</p>
                            </div>
                        @endif
                        @if($entryDontExample)
                            <div class="p-3 rounded-lg border-2 border-red-200 bg-red-50/50">
                                <div class="flex items-center gap-1 mb-1">
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-500">
                                        @svg('heroicon-o-x-mark', 'w-2.5 h-2.5 text-white')
                                    </span>
                                    <span class="text-[10px] font-bold text-red-800 uppercase">Don't</span>
                                </div>
                                <p class="text-xs text-red-900">{{ Str::limit($entryDontExample, 100) }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>

    <x-slot name="footer">
        <x-ui-button variant="success" wire:click="save">
            {{ $entry ? 'Aktualisieren' : 'Erstellen' }}
        </x-ui-button>
    </x-slot>
</x-ui-modal>
