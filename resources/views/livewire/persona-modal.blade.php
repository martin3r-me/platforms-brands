<div>
    @if($modalShow)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit="save">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-100 to-teal-50 flex items-center justify-center">
                                    @svg('heroicon-o-user', 'w-5 h-5 text-teal-600')
                                </div>
                                <h3 class="text-lg font-bold text-[var(--ui-secondary)]">
                                    {{ $persona ? 'Persona bearbeiten' : 'Neue Persona erstellen' }}
                                </h3>
                            </div>

                            <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
                                {{-- Basic Info --}}
                                <div class="space-y-4">
                                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)] border-b border-[var(--ui-border)]/40 pb-2">Grunddaten</h4>

                                    <div>
                                        <label for="personaName" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Name *</label>
                                        <input type="text" id="personaName" wire:model="personaName" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="z.B. Marketing-Maria, Tech-Thomas">
                                        @error('personaName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="personaAge" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Alter</label>
                                            <input type="number" id="personaAge" wire:model="personaAge" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="z.B. 35" min="1" max="120">
                                        </div>
                                        <div>
                                            <label for="personaGender" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Geschlecht</label>
                                            <select id="personaGender" wire:model="personaGender" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
                                                <option value="">-- Auswahl --</option>
                                                @foreach(\Platform\Brands\Models\BrandsPersona::GENDERS as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="personaOccupation" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Beruf</label>
                                            <input type="text" id="personaOccupation" wire:model="personaOccupation" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="z.B. Marketing Managerin">
                                        </div>
                                        <div>
                                            <label for="personaLocation" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Wohnort</label>
                                            <input type="text" id="personaLocation" wire:model="personaLocation" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="z.B. M&uuml;nchen">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="personaEducation" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Bildung</label>
                                            <input type="text" id="personaEducation" wire:model="personaEducation" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="z.B. Master BWL">
                                        </div>
                                        <div>
                                            <label for="personaIncomeRange" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Einkommen</label>
                                            <input type="text" id="personaIncomeRange" wire:model="personaIncomeRange" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="z.B. 50.000-70.000 EUR">
                                        </div>
                                    </div>

                                    <div>
                                        <label for="personaBio" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Biografie / Kurzbeschreibung</label>
                                        <textarea id="personaBio" wire:model="personaBio" rows="3" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Kurze Beschreibung der Persona..."></textarea>
                                    </div>
                                </div>

                                {{-- Pain Points --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                                            @svg('heroicon-o-exclamation-triangle', 'w-4 h-4 text-red-500')
                                            Pain Points
                                        </h4>
                                        <button type="button" wire:click="addPainPoint" class="text-xs text-teal-600 hover:text-teal-700 font-medium">+ Hinzuf&uuml;gen</button>
                                    </div>
                                    @foreach($personaPainPoints as $index => $point)
                                        <div class="flex items-center gap-2">
                                            <input type="text" wire:model="personaPainPoints.{{ $index }}.text" class="flex-1 rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Pain Point...">
                                            <button type="button" wire:click="removePainPoint({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Goals --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                                            @svg('heroicon-o-flag', 'w-4 h-4 text-green-500')
                                            Ziele
                                        </h4>
                                        <button type="button" wire:click="addGoal" class="text-xs text-teal-600 hover:text-teal-700 font-medium">+ Hinzuf&uuml;gen</button>
                                    </div>
                                    @foreach($personaGoals as $index => $goal)
                                        <div class="flex items-center gap-2">
                                            <input type="text" wire:model="personaGoals.{{ $index }}.text" class="flex-1 rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Ziel...">
                                            <button type="button" wire:click="removeGoal({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Quotes --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                                            @svg('heroicon-o-chat-bubble-bottom-center-text', 'w-4 h-4 text-amber-500')
                                            Typische Zitate
                                        </h4>
                                        <button type="button" wire:click="addQuote" class="text-xs text-teal-600 hover:text-teal-700 font-medium">+ Hinzuf&uuml;gen</button>
                                    </div>
                                    @foreach($personaQuotes as $index => $quote)
                                        <div class="flex items-center gap-2">
                                            <input type="text" wire:model="personaQuotes.{{ $index }}.text" class="flex-1 rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Zitat...">
                                            <button type="button" wire:click="removeQuote({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Behaviors --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                                            @svg('heroicon-o-finger-print', 'w-4 h-4 text-blue-500')
                                            Verhalten / Gewohnheiten
                                        </h4>
                                        <button type="button" wire:click="addBehavior" class="text-xs text-teal-600 hover:text-teal-700 font-medium">+ Hinzuf&uuml;gen</button>
                                    </div>
                                    @foreach($personaBehaviors as $index => $behavior)
                                        <div class="flex items-center gap-2">
                                            <input type="text" wire:model="personaBehaviors.{{ $index }}.text" class="flex-1 rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Verhalten...">
                                            <button type="button" wire:click="removeBehavior({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Channels --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                                            @svg('heroicon-o-signal', 'w-4 h-4 text-purple-500')
                                            Bevorzugte Kan&auml;le
                                        </h4>
                                        <button type="button" wire:click="addChannel" class="text-xs text-teal-600 hover:text-teal-700 font-medium">+ Hinzuf&uuml;gen</button>
                                    </div>
                                    @foreach($personaChannels as $index => $channel)
                                        <div class="flex items-center gap-2">
                                            <input type="text" wire:model="personaChannels.{{ $index }}.text" class="flex-1 rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="z.B. Instagram, LinkedIn...">
                                            <button type="button" wire:click="removeChannel({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Brands Liked --}}
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] flex items-center gap-2">
                                            @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                            Lieblingsmarken
                                        </h4>
                                        <button type="button" wire:click="addBrandLiked" class="text-xs text-teal-600 hover:text-teal-700 font-medium">+ Hinzuf&uuml;gen</button>
                                    </div>
                                    @foreach($personaBrandsLiked as $index => $brand)
                                        <div class="flex items-center gap-2">
                                            <input type="text" wire:model="personaBrandsLiked.{{ $index }}.text" class="flex-1 rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Marke...">
                                            <button type="button" wire:click="removeBrandLiked({{ $index }})" class="p-2 text-[var(--ui-muted)] hover:text-red-500 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Tone of Voice Verknüpfung --}}
                                <div class="space-y-3">
                                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)] border-b border-[var(--ui-border)]/40 pb-2 flex items-center gap-2">
                                        @svg('heroicon-o-megaphone', 'w-4 h-4 text-violet-500')
                                        Tone of Voice Zuordnung
                                    </h4>
                                    <div>
                                        <label for="personaToneOfVoiceBoardId" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Welcher Ton f&uuml;r diese Persona?</label>
                                        <select id="personaToneOfVoiceBoardId" wire:model="personaToneOfVoiceBoardId" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-violet-500 focus:ring-violet-500">
                                            <option value="">-- Kein Tone of Voice Board --</option>
                                            @foreach($toneOfVoiceBoards as $tovBoard)
                                                <option value="{{ $tovBoard->id }}">{{ $tovBoard->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-[var(--ui-muted)] mt-1">Ordne ein Tone of Voice Board zu, um den Kommunikationston f&uuml;r diese Zielgruppe festzulegen.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-[var(--ui-muted-5)] px-6 py-4 flex items-center justify-end gap-3 border-t border-[var(--ui-border)]/40">
                            <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="closeModal">
                                Abbrechen
                            </x-ui-button>
                            <x-ui-button variant="primary" size="sm" type="submit">
                                {{ $persona ? 'Speichern' : 'Erstellen' }}
                            </x-ui-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
