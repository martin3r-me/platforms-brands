<x-nx-modal size="lg" model="modalShow" header="{{ $entry ? 'Messaging-Eintrag bearbeiten' : 'Neuer Messaging-Eintrag' }}">
    <div class="space-y-4">
        {{-- Name & Type --}}
        <div class="grid grid-cols-2 gap-4">
            <x-nx-input-text
                name="entryName"
                label="Name"
                wire:model.live.debounce.300ms="entryName"
                placeholder="z.B. Haupt-Slogan, Elevator Pitch..."
                required
                :errorKey="'entryName'"
            />
            <div>
                <label class="block text-sm font-medium text-[color:var(--nx-text)] mb-1">Typ</label>
                <select wire:model.live="entryType" class="w-full px-3 py-2 text-sm border border-[color:var(--nx-line-strong)] rounded-lg bg-[color:var(--nx-surface)] text-[color:var(--nx-text)] focus:ring-2 focus:ring-[color:var(--nx-accent)] focus:border-transparent">
                    @foreach(\Platform\Brands\Models\BrandsToneOfVoiceEntry::TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Content --}}
        <x-nx-input-textarea
            name="entryContent"
            label="Inhalt / Text"
            wire:model.live.debounce.300ms="entryContent"
            placeholder="Der eigentliche Slogan, die Kernbotschaft, der Elevator Pitch..."
            required
            :errorKey="'entryContent'"
        />

        {{-- Description --}}
        <x-nx-input-textarea
            name="entryDescription"
            label="Kontext / Beschreibung"
            wire:model.live.debounce.300ms="entryDescription"
            placeholder="Wann und wo wird dieser Text verwendet? Zielgruppe, Kanal..."
            :errorKey="'entryDescription'"
        />

        {{-- Example Texts --}}
        <div class="border border-[color:var(--nx-line)] rounded-[8px] overflow-hidden">
            <div class="bg-[color:var(--nx-hover)] px-4 py-3 border-b border-[color:var(--nx-line)]">
                <h3 class="text-sm font-semibold text-[color:var(--nx-text)]">Beispiel-Texte (Tonalität)</h3>
                <p class="text-xs text-[color:var(--nx-faint)]">Zeige, wie die Markenstimme klingen soll - und wie nicht</p>
            </div>
            <div class="p-4 space-y-4">
                {{-- So ja --}}
                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-[color:var(--nx-text)] mb-1">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100">
                            @svg('heroicon-o-check', 'w-3 h-3 text-green-600')
                        </span>
                        So ja (positives Beispiel)
                    </label>
                    <textarea
                        wire:model.live.debounce.300ms="entryExamplePositive"
                        placeholder="Beispieltext, der zeigt wie es richtig klingt..."
                        rows="2"
                        class="w-full px-3 py-2 text-sm border border-green-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent bg-green-50/50"
                    ></textarea>
                </div>

                {{-- So nein --}}
                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-[color:var(--nx-text)] mb-1">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-100">
                            @svg('heroicon-o-x-mark', 'w-3 h-3 text-red-600')
                        </span>
                        So nein (negatives Beispiel)
                    </label>
                    <textarea
                        wire:model.live.debounce.300ms="entryExampleNegative"
                        placeholder="Beispieltext, der zeigt wie es NICHT klingen soll..."
                        rows="2"
                        class="w-full px-3 py-2 text-sm border border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent bg-red-50/50"
                    ></textarea>
                </div>
            </div>
        </div>

        {{-- Live Preview --}}
        @if($entryContent)
            <div class="p-4 bg-[color:var(--nx-hover)] rounded-[8px] border border-[color:var(--nx-line)]">
                <div class="text-xs font-semibold text-[color:var(--nx-faint)] mb-3 uppercase tracking-wider">Vorschau</div>
                @php
                    $previewTypeColors = [
                        'slogan' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'elevator_pitch' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'core_message' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'value' => 'bg-purple-50 text-purple-700 border-purple-200',
                        'claim' => 'bg-rose-50 text-rose-700 border-rose-200',
                    ];
                    $previewColors = $previewTypeColors[$entryType] ?? $previewTypeColors['core_message'];
                @endphp
                <span class="inline-flex items-center px-2.5 py-1 rounded-md {{ $previewColors }} text-xs font-bold uppercase tracking-wider border mb-2">
                    {{ \Platform\Brands\Models\BrandsToneOfVoiceEntry::TYPES[$entryType] ?? $entryType }}
                </span>
                <p class="text-sm text-[color:var(--nx-text)] leading-relaxed">{{ $entryContent }}</p>
            </div>
        @endif
    </div>

    <x-slot name="footer">
        <x-nx-button variant="primary" wire:click="save">
            {{ $entry ? 'Aktualisieren' : 'Erstellen' }}
        </x-nx-button>
    </x-slot>
</x-nx-modal>
