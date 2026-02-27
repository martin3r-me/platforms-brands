<x-ui-modal size="lg" model="modalShow" header="SEO Board-Einstellungen">
    @if($seoBoard)
        <div class="space-y-6">
            {{-- Allgemein --}}
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Allgemein</h3>
                <x-ui-form-grid :cols="1" :gap="4">
                    @can('update', $seoBoard)
                        <x-ui-input-text
                            name="seoBoard.name"
                            label="Board Name"
                            wire:model.live.debounce.500ms="seoBoard.name"
                            placeholder="SEO Board Name eingeben..."
                            required
                            :errorKey="'seoBoard.name'"
                        />
                        <x-ui-input-textarea
                            name="seoBoard.description"
                            label="Beschreibung"
                            wire:model.live.debounce.500ms="seoBoard.description"
                            placeholder="Beschreibung des SEO Boards eingeben..."
                            :errorKey="'seoBoard.description'"
                        />
                    @else
                        <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                            <span class="text-[var(--ui-muted)]">Board Name</span>
                            <span class="font-medium text-[var(--ui-body-color)]">{{ $seoBoard->name }}</span>
                        </div>
                    @endcan
                </x-ui-form-grid>
            </div>

            {{-- DataForSEO Konfiguration --}}
            @can('update', $seoBoard)
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">
                        @svg('heroicon-o-globe-alt', 'w-3.5 h-3.5 inline-block mr-1')
                        DataForSEO Konfiguration
                    </h3>
                    <x-ui-form-grid :cols="1" :gap="4">
                        <x-ui-input-text
                            name="configLocationCode"
                            label="Location Code"
                            wire:model.live.debounce.500ms="configLocationCode"
                            placeholder="z.B. 2276 (Deutschland)"
                            :errorKey="'configLocationCode'"
                        />
                        <x-ui-input-text
                            name="configLanguageName"
                            label="Sprache"
                            wire:model.live.debounce.500ms="configLanguageName"
                            placeholder="z.B. German"
                            :errorKey="'configLanguageName'"
                        />
                        <x-ui-input-text
                            name="configConnectionId"
                            label="Connection ID (optional)"
                            wire:model.live.debounce.500ms="configConnectionId"
                            placeholder="Standard-Connection des Teams"
                            :errorKey="'configConnectionId'"
                        />
                    </x-ui-form-grid>
                    <div class="mt-2 text-[10px] text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded p-2 border border-[var(--ui-border)]/40">
                        <strong>Location Codes:</strong> 2276 = Deutschland, 2040 = &Ouml;sterreich, 2756 = Schweiz, 2826 = UK, 2840 = USA
                    </div>
                </div>

                {{-- Automatischer Refresh --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">
                        @svg('heroicon-o-arrow-path', 'w-3.5 h-3.5 inline-block mr-1')
                        Automatischer Refresh
                    </h3>
                    <x-ui-form-grid :cols="1" :gap="4">
                        <x-ui-input-select
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
                    </x-ui-form-grid>
                    @if($seoBoard->last_refreshed_at)
                        <div class="mt-2 text-[10px] text-[var(--ui-muted)]">
                            Letzter Refresh: {{ $seoBoard->last_refreshed_at->format('d.m.Y H:i') }} ({{ $seoBoard->last_refreshed_at->diffForHumans() }})
                        </div>
                    @endif
                </div>

                {{-- Budget --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">
                        @svg('heroicon-o-banknotes', 'w-3.5 h-3.5 inline-block mr-1')
                        API-Budget
                    </h3>
                    <x-ui-form-grid :cols="1" :gap="4">
                        <x-ui-input-text
                            name="budgetLimitEuro"
                            label="Budget-Limit in Euro (leer = unbegrenzt)"
                            wire:model.live.debounce.500ms="budgetLimitEuro"
                            placeholder="z.B. 10.00"
                            :errorKey="'budgetLimitEuro'"
                        />
                    </x-ui-form-grid>
                    <div class="mt-2 flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-[var(--ui-muted-5)]">
                        <span class="text-[var(--ui-muted)]">Verbraucht</span>
                        <span class="font-semibold text-[var(--ui-secondary)]">{{ number_format(($seoBoard->budget_spent_cents ?? 0) / 100, 2) }} &euro;</span>
                    </div>
                    @if($seoBoard->budget_spent_cents > 0)
                        <div class="mt-2">
                            <x-ui-confirm-button action="resetBudget" text="Budget zur&uuml;cksetzen" confirmText="Wirklich zur&uuml;cksetzen?" />
                        </div>
                    @endif
                </div>
            @endcan

            {{-- Board l&ouml;schen --}}
            @can('delete', $seoBoard)
                <div class="pt-4 border-t border-red-200">
                    <x-ui-confirm-button action="deleteSeoBoard" text="SEO Board l&ouml;schen" confirmText="Wirklich l&ouml;schen?" />
                </div>
            @endcan
        </div>

        <x-slot name="footer">
            @can('update', $seoBoard)
                <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
            @endcan
        </x-slot>
    @endif
</x-ui-modal>
