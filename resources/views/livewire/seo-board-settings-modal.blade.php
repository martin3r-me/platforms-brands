<x-nx-modal size="lg" model="modalShow" header="SEO Board-Einstellungen">
    @if($seoBoard)
        <div class="space-y-6">
            {{-- Allgemein --}}
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-faint)] mb-3">Allgemein</h3>
                <div class="space-y-4">
                    @can('update', $seoBoard)
                        <x-nx-input-text
                            name="seoBoard.name"
                            label="Board Name"
                            wire:model.live.debounce.500ms="seoBoard.name"
                            placeholder="SEO Board Name eingeben..."
                            required
                            :errorKey="'seoBoard.name'"
                        />
                        <x-nx-input-textarea
                            name="seoBoard.description"
                            label="Beschreibung"
                            wire:model.live.debounce.500ms="seoBoard.description"
                            placeholder="Beschreibung des SEO Boards eingeben..."
                            :errorKey="'seoBoard.description'"
                        />
                    @else
                        <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--nx-line)] bg-[color:var(--nx-surface)]">
                            <span class="text-[var(--nx-faint)]">Board Name</span>
                            <span class="font-medium text-[var(--nx-text)]">{{ $seoBoard->name }}</span>
                        </div>
                    @endcan
                </div>
            </div>

            {{-- DataForSEO Konfiguration --}}
            @can('update', $seoBoard)
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-faint)] mb-3">
                        @svg('heroicon-o-globe-alt', 'w-3.5 h-3.5 inline-block mr-1')
                        DataForSEO Konfiguration
                    </h3>
                    <div class="space-y-4">
                        <x-nx-input-text
                            name="configLocationCode"
                            label="Location Code"
                            wire:model.live.debounce.500ms="configLocationCode"
                            placeholder="z.B. 2276 (Deutschland)"
                            :errorKey="'configLocationCode'"
                        />
                        <x-nx-input-text
                            name="configLanguageName"
                            label="Sprache"
                            wire:model.live.debounce.500ms="configLanguageName"
                            placeholder="z.B. German"
                            :errorKey="'configLanguageName'"
                        />
                        <x-nx-input-text
                            name="configConnectionId"
                            label="Connection ID (optional)"
                            wire:model.live.debounce.500ms="configConnectionId"
                            placeholder="Standard-Connection des Teams"
                            :errorKey="'configConnectionId'"
                        />
                    </div>
                    <div class="mt-2 text-[10px] text-[var(--nx-faint)] bg-[var(--nx-hover)] rounded p-2 border border-[var(--nx-line)]">
                        <strong>Location Codes:</strong> 2276 = Deutschland, 2040 = &Ouml;sterreich, 2756 = Schweiz, 2826 = UK, 2840 = USA
                    </div>
                </div>

                {{-- Automatischer Refresh --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-faint)] mb-3">
                        @svg('heroicon-o-arrow-path', 'w-3.5 h-3.5 inline-block mr-1')
                        Automatischer Refresh
                    </h3>
                    <div class="space-y-4">
                        <x-nx-input-select
                            name="refreshIntervalDays"
                            label="Refresh-Intervall"
                            :options="$refreshIntervalOptions"
                            optionValue="value"
                            optionLabel="label"
                            :nullable="true"
                            nullLabel="Deaktiviert"
                            wire:model.live="refreshIntervalDays"
                            :errorKey="'refreshIntervalDays'"
                        />
                    </div>
                    @if($seoBoard->last_refreshed_at)
                        <div class="mt-2 text-[10px] text-[var(--nx-faint)]">
                            Letzter Refresh: {{ $seoBoard->last_refreshed_at->format('d.m.Y H:i') }} ({{ $seoBoard->last_refreshed_at->diffForHumans() }})
                        </div>
                    @endif
                </div>

                {{-- Budget --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--nx-faint)] mb-3">
                        @svg('heroicon-o-banknotes', 'w-3.5 h-3.5 inline-block mr-1')
                        API-Budget
                    </h3>
                    <div class="space-y-4">
                        <x-nx-input-text
                            name="budgetLimitEuro"
                            label="Budget-Limit in Euro (leer = unbegrenzt)"
                            wire:model.live.debounce.500ms="budgetLimitEuro"
                            placeholder="z.B. 10.00"
                            :errorKey="'budgetLimitEuro'"
                        />
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm p-2 rounded border border-[var(--nx-line)] bg-[var(--nx-hover)]">
                        <span class="text-[var(--nx-faint)]">Verbraucht</span>
                        <span class="font-semibold text-[var(--nx-text)]">{{ number_format(($seoBoard->budget_spent_cents ?? 0) / 100, 2) }} &euro;</span>
                    </div>
                    @if($seoBoard->budget_spent_cents > 0)
                        <div class="mt-2">
                            <x-nx-button variant="danger" size="sm" wire:click="resetBudget" wire:confirm="Wirklich zur&uuml;cksetzen?">
                                @svg('heroicon-o-trash', 'w-4 h-4') Budget zur&uuml;cksetzen
                            </x-nx-button>
                        </div>
                    @endif
                </div>
            @endcan

            {{-- Board l&ouml;schen --}}
            @can('delete', $seoBoard)
                <div class="pt-4 border-t border-[var(--nx-line)]">
                    <x-nx-button variant="danger" size="sm" wire:click="deleteSeoBoard" wire:confirm="Wirklich l&ouml;schen?">
                        @svg('heroicon-o-trash', 'w-4 h-4') SEO Board l&ouml;schen
                    </x-nx-button>
                </div>
            @endcan
        </div>

        <x-slot name="footer">
            @can('update', $seoBoard)
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            @endcan
        </x-slot>
    @endif
</x-nx-modal>
