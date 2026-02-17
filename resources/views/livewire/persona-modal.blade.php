<x-ui-modal size="lg" wire:model="modalShow">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-[var(--ui-secondary)] m-0">
                    {{ $persona ? 'Persona bearbeiten' : 'Neue Persona erstellen' }}
                </h2>
                <span class="text-xs text-[var(--ui-muted)] bg-[var(--ui-muted-5)] px-2 py-1 rounded-full">PERSONA</span>
            </div>
        </div>
    </x-slot>

    <form wire:submit="save">
        <div class="space-y-6">
            {{-- Grunddaten --}}
            <div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Grunddaten</h3>
                <div class="space-y-4">
                    <x-ui-input-text
                        name="personaName"
                        label="Name *"
                        wire:model.live.debounce.500ms="personaName"
                        placeholder="z.B. Marketing-Maria, Tech-Thomas"
                        :errorKey="'personaName'"
                    />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui-input-text
                            name="personaAge"
                            label="Alter"
                            type="number"
                            wire:model.live.debounce.500ms="personaAge"
                            placeholder="z.B. 35"
                            :errorKey="'personaAge'"
                        />
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1.5">Geschlecht</label>
                            <select
                                wire:model="personaGender"
                                class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg bg-[var(--ui-surface)] text-[var(--ui-secondary)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                            >
                                <option value="">-- Auswahl --</option>
                                @foreach(\Platform\Brands\Models\BrandsPersona::GENDERS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui-input-text
                            name="personaOccupation"
                            label="Beruf"
                            wire:model.live.debounce.500ms="personaOccupation"
                            placeholder="z.B. Marketing Managerin"
                            :errorKey="'personaOccupation'"
                        />
                        <x-ui-input-text
                            name="personaLocation"
                            label="Wohnort"
                            wire:model.live.debounce.500ms="personaLocation"
                            placeholder="z.B. M&uuml;nchen"
                            :errorKey="'personaLocation'"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui-input-text
                            name="personaEducation"
                            label="Bildung"
                            wire:model.live.debounce.500ms="personaEducation"
                            placeholder="z.B. Master BWL"
                            :errorKey="'personaEducation'"
                        />
                        <x-ui-input-text
                            name="personaIncomeRange"
                            label="Einkommen"
                            wire:model.live.debounce.500ms="personaIncomeRange"
                            placeholder="z.B. 50.000-70.000 EUR"
                            :errorKey="'personaIncomeRange'"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1.5">Biografie / Kurzbeschreibung</label>
                        <textarea
                            wire:model.live.debounce.500ms="personaBio"
                            rows="3"
                            class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg bg-[var(--ui-surface)] text-[var(--ui-secondary)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                            placeholder="Kurze Beschreibung der Persona..."
                        ></textarea>
                    </div>
                </div>
            </div>

            {{-- Pain Points --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-exclamation-triangle', 'w-5 h-5 text-red-500')
                        Pain Points
                    </h3>
                    <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="addPainPoint">
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Hinzuf&uuml;gen
                        </div>
                    </x-ui-button>
                </div>
                @if(count($personaPainPoints) > 0)
                    <div class="space-y-2">
                        @foreach($personaPainPoints as $index => $point)
                            <div class="flex items-center gap-2">
                                <x-ui-input-text
                                    name="personaPainPoints.{{ $index }}.text"
                                    wire:model.live.debounce.500ms="personaPainPoints.{{ $index }}.text"
                                    placeholder="Pain Point..."
                                />
                                <button type="button" wire:click="removePainPoint({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                        Noch keine Pain Points hinzugef&uuml;gt.
                    </div>
                @endif
            </div>

            {{-- Ziele --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-flag', 'w-5 h-5 text-green-500')
                        Ziele
                    </h3>
                    <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="addGoal">
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Hinzuf&uuml;gen
                        </div>
                    </x-ui-button>
                </div>
                @if(count($personaGoals) > 0)
                    <div class="space-y-2">
                        @foreach($personaGoals as $index => $goal)
                            <div class="flex items-center gap-2">
                                <x-ui-input-text
                                    name="personaGoals.{{ $index }}.text"
                                    wire:model.live.debounce.500ms="personaGoals.{{ $index }}.text"
                                    placeholder="Ziel..."
                                />
                                <button type="button" wire:click="removeGoal({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                        Noch keine Ziele hinzugef&uuml;gt.
                    </div>
                @endif
            </div>

            {{-- Typische Zitate --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-chat-bubble-bottom-center-text', 'w-5 h-5 text-amber-500')
                        Typische Zitate
                    </h3>
                    <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="addQuote">
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Hinzuf&uuml;gen
                        </div>
                    </x-ui-button>
                </div>
                @if(count($personaQuotes) > 0)
                    <div class="space-y-2">
                        @foreach($personaQuotes as $index => $quote)
                            <div class="flex items-center gap-2">
                                <x-ui-input-text
                                    name="personaQuotes.{{ $index }}.text"
                                    wire:model.live.debounce.500ms="personaQuotes.{{ $index }}.text"
                                    placeholder="Zitat..."
                                />
                                <button type="button" wire:click="removeQuote({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                        Noch keine Zitate hinzugef&uuml;gt.
                    </div>
                @endif
            </div>

            {{-- Verhalten / Gewohnheiten --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-finger-print', 'w-5 h-5 text-blue-500')
                        Verhalten / Gewohnheiten
                    </h3>
                    <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="addBehavior">
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Hinzuf&uuml;gen
                        </div>
                    </x-ui-button>
                </div>
                @if(count($personaBehaviors) > 0)
                    <div class="space-y-2">
                        @foreach($personaBehaviors as $index => $behavior)
                            <div class="flex items-center gap-2">
                                <x-ui-input-text
                                    name="personaBehaviors.{{ $index }}.text"
                                    wire:model.live.debounce.500ms="personaBehaviors.{{ $index }}.text"
                                    placeholder="Verhalten..."
                                />
                                <button type="button" wire:click="removeBehavior({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                        Noch keine Verhaltensweisen hinzugef&uuml;gt.
                    </div>
                @endif
            </div>

            {{-- Bevorzugte Kan&auml;le --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-signal', 'w-5 h-5 text-purple-500')
                        Bevorzugte Kan&auml;le
                    </h3>
                    <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="addChannel">
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Hinzuf&uuml;gen
                        </div>
                    </x-ui-button>
                </div>
                @if(count($personaChannels) > 0)
                    <div class="space-y-2">
                        @foreach($personaChannels as $index => $channel)
                            <div class="flex items-center gap-2">
                                <x-ui-input-text
                                    name="personaChannels.{{ $index }}.text"
                                    wire:model.live.debounce.500ms="personaChannels.{{ $index }}.text"
                                    placeholder="z.B. Instagram, LinkedIn..."
                                />
                                <button type="button" wire:click="removeChannel({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                        Noch keine Kan&auml;le hinzugef&uuml;gt.
                    </div>
                @endif
            </div>

            {{-- Lieblingsmarken --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-heart', 'w-5 h-5 text-pink-500')
                        Lieblingsmarken
                    </h3>
                    <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="addBrandLiked">
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            Hinzuf&uuml;gen
                        </div>
                    </x-ui-button>
                </div>
                @if(count($personaBrandsLiked) > 0)
                    <div class="space-y-2">
                        @foreach($personaBrandsLiked as $index => $brand)
                            <div class="flex items-center gap-2">
                                <x-ui-input-text
                                    name="personaBrandsLiked.{{ $index }}.text"
                                    wire:model.live.debounce.500ms="personaBrandsLiked.{{ $index }}.text"
                                    placeholder="Marke..."
                                />
                                <button type="button" wire:click="removeBrandLiked({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-4 h-4')
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                        Noch keine Lieblingsmarken hinzugef&uuml;gt.
                    </div>
                @endif
            </div>

            {{-- Tone of Voice Zuordnung --}}
            <div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4 flex items-center gap-2">
                    @svg('heroicon-o-megaphone', 'w-5 h-5 text-violet-500')
                    Tone of Voice Zuordnung
                </h3>
                <div class="p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                    <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1.5">Welcher Ton f&uuml;r diese Persona?</label>
                    <select
                        wire:model="personaToneOfVoiceBoardId"
                        class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg bg-[var(--ui-surface)] text-[var(--ui-secondary)] text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                    >
                        <option value="">-- Kein Tone of Voice Board --</option>
                        @foreach($toneOfVoiceBoards as $tovBoard)
                            <option value="{{ $tovBoard->id }}">{{ $tovBoard->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-[var(--ui-muted)] mt-1.5">Ordne ein Tone of Voice Board zu, um den Kommunikationston f&uuml;r diese Zielgruppe festzulegen.</p>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button variant="secondary-outline" type="button" @click="modalShow = false">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" type="submit">
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        {{ $persona ? 'Speichern' : 'Erstellen' }}
                    </div>
                </x-ui-button>
            </div>
        </x-slot>
    </form>
</x-ui-modal>
