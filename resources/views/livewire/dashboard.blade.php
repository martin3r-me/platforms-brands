<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" icon="heroicon-o-home" />
    </x-slot>

    <x-ui-page-container>

            {{-- Main Stats Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-ui-dashboard-tile
                    title="Aktive Marken"
                    :count="$activeBrands"
                    subtitle="von {{ $totalBrands }}"
                    icon="tag"
                    variant="secondary"
                    size="lg"
                />
            </div>

            <x-ui-panel title="Meine aktiven Marken" subtitle="Top 5 Marken">
                <div class="grid grid-cols-1 gap-3">
                    @forelse($activeBrandsList as $brand)
                        @php
                            $href = route('brands.brands.show', ['brandsBrand' => $brand['id'] ?? null]);
                        @endphp
                        <a href="{{ $href }}" class="flex items-center gap-3 p-3 rounded-md border border-[var(--ui-border)] bg-white hover:bg-[var(--ui-muted-5)] transition">
                            <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded flex items-center justify-center">
                                @svg('heroicon-o-tag', 'w-5 h-5')
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $brand['name'] ?? 'Marke' }}</div>
                                <div class="text-xs text-[var(--ui-muted)] truncate">
                                    {{ $brand['subtitle'] ?? '' }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-3 text-sm text-[var(--ui-muted)] bg-white rounded-md border border-[var(--ui-border)]">Keine Marken gefunden.</div>
                    @endforelse
                </div>
            </x-ui-panel>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        {{-- Platzhalter für zukünftige Aktionen --}}
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Schnellstatistiken</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-xs text-[var(--ui-muted)]">Aktive Marken</div>
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $activeBrands ?? 0 }} Marken</div>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity (Dummy) --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Letzte Aktivitäten</h3>
                    <div class="space-y-2 text-sm">
                        <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                            <div class="font-medium text-[var(--ui-secondary)] truncate">Dashboard geladen</div>
                            <div class="text-[var(--ui-muted)] text-xs">vor 1 Minute</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="space-y-3 text-sm">
                    <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                        <div class="font-medium text-[var(--ui-secondary)] truncate">Dashboard geladen</div>
                        <div class="text-[var(--ui-muted)]">vor 1 Minute</div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
