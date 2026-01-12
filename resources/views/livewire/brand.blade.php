{{-- Root auf x-ui-page umstellen, damit volle Höhe/Sidebars korrekt funktionieren --}}
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$brand->name" icon="heroicon-o-tag" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Marken-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $brand)
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-brand-settings', { brandId: {{ $brand->id }} })">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                                    <span>Einstellungen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                </div>

                {{-- Marken-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $brand->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($brand->done)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Status</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded bg-[var(--ui-success-5)] text-[var(--ui-success)]">
                                    Erledigt
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Hauptinhalt --}}
    <div class="p-6">
        @if($brand->description)
            <div class="mb-6">
                <p class="text-[var(--ui-secondary)]">{{ $brand->description }}</p>
            </div>
        @endif

        <div class="mt-8">
            <p class="text-[var(--ui-muted)]">Die Marken-Ansicht wird hier implementiert.</p>
        </div>
    </div>
</x-ui-page>
