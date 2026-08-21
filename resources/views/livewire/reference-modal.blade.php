<x-nx-modal size="lg" model="modalShow" :header="$isEdit ? 'Referenz bearbeiten' : 'Referenz hinzufügen'">
    <div class="space-y-5">

        {{-- URL + Vorschau laden --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-[color:var(--nx-text)]">URL <span class="text-[color:var(--nx-danger)]">*</span></label>
            <div class="flex items-center gap-2">
                <input type="text" wire:model="url" placeholder="example.com"
                       class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-2.5 py-2 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                <x-nx-button variant="secondary" size="sm" wire:click="fetchPreview" wire:loading.attr="disabled" wire:target="fetchPreview" class="shrink-0">
                    <span wire:loading.remove wire:target="fetchPreview" class="inline-flex items-center gap-1.5 whitespace-nowrap">@svg('heroicon-o-arrow-down-tray', 'w-4 h-4') Vorschau</span>
                    <span wire:loading wire:target="fetchPreview" class="whitespace-nowrap">Lädt…</span>
                </x-nx-button>
            </div>
            @error('url')<p class="mt-1 text-xs text-[color:var(--nx-danger)]">{{ $message }}</p>@enderror
            @if(session('reference_error'))<p class="mt-1 text-xs text-[color:var(--nx-warning)]">{{ session('reference_error') }}</p>@endif
        </div>

        {{-- Screenshot-Vorschau --}}
        @if($screenshotUrl)
            <div class="overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-accent-soft)]">
                <img src="{{ $screenshotUrl }}" alt="Vorschau" class="max-h-52 w-full object-cover">
            </div>
        @endif

        {{-- Verdikt (segmentiert) --}}
        <div>
            <label class="mb-1.5 block text-xs font-medium text-[color:var(--nx-text)]">Bewertung</label>
            <div class="inline-flex rounded-[8px] border border-[color:var(--nx-line-strong)] p-0.5">
                @foreach([['like','Gefällt uns','emerald'],['neutral','Neutral','slate'],['dislike','Gefällt uns nicht','rose']] as [$val,$label,$col])
                    <button type="button" wire:click="$set('verdict', '{{ $val }}')"
                            @class([
                                'rounded-[6px] px-3 py-1.5 text-[13px] font-medium transition-colors',
                                'bg-emerald-500 text-white' => $verdict === $val && $val === 'like',
                                'bg-rose-500 text-white' => $verdict === $val && $val === 'dislike',
                                'bg-[color:var(--nx-text)] text-white' => $verdict === $val && $val === 'neutral',
                                'text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' => $verdict !== $val,
                            ])>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- Begründung --}}
        <x-nx-input-textarea name="reason" label="Begründung" :rows="4" wire:model="reason"
                             placeholder="Was gefällt (nicht)? Was ist die Idee dahinter? – das Herzstück der Referenz." hint="Der eigentliche Wert der Liste" />

        {{-- Aspekte --}}
        <div>
            <label class="mb-1.5 block text-xs font-medium text-[color:var(--nx-text)]">Aspekte</label>
            <div class="flex flex-wrap gap-1.5">
                @foreach($aspectLabels as $key => $label)
                    <button type="button" wire:click="toggleAspect('{{ $key }}')"
                            @class([
                                'rounded-full px-2.5 py-1 text-[12.5px] font-medium transition-colors',
                                'bg-[color:var(--nx-text)] text-white' => in_array($key, $aspects, true),
                                'border border-[color:var(--nx-line-strong)] text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)]' => !in_array($key, $aspects, true),
                            ])>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- Titel + Branche --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-nx-input-text name="title" label="Titel" wire:model="title" placeholder="z. B. Aesop – Produktseite" hint="Optional, sonst Domain" />
            <x-nx-input-text name="industry" label="Branche" wire:model="industry" placeholder="z. B. Kosmetik, SaaS…" hint="Optional" />
        </div>
    </div>

    @if($isEdit)
        <div class="mt-5">
            <x-nx-button variant="danger" size="sm" wire:click="deleteReference" wire:confirm="Referenz wirklich löschen?">
                @svg('heroicon-o-trash', 'w-4 h-4') Referenz löschen
            </x-nx-button>
        </div>
    @endif

    <x-slot name="footer">
        <x-nx-button variant="ghost" wire:click="closeModal">Abbrechen</x-nx-button>
        <x-nx-button variant="primary" wire:click="save"
                     wire:loading.attr="disabled" wire:target="save,fetchPreview"
                     wire:loading.class="opacity-50 pointer-events-none">
            <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Aktualisieren' : 'Hinzufügen' }}</span>
            <span wire:loading wire:target="save" class="whitespace-nowrap">Speichert…</span>
        </x-nx-button>
    </x-slot>
</x-nx-modal>
