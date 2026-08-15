<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Export" icon="heroicon-o-arrow-down-tray" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $brand->name, 'href' => route('brands.brands.show', $brand)],
            ['label' => 'Export'],
        ]" />
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold text-[color:var(--nx-text)]">Export</h1>
            <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">Marke und einzelne Boards als JSON oder PDF exportieren.</p>
        </div>

        {{-- Komplette Marke exportieren --}}
        <x-nx-section icon="heroicon-o-building-office" title="Komplette Marke exportieren" description="Alle Boards, Einträge, Einstellungen und Medien-Referenzen">
            <x-nx-card>
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-[15px] font-semibold text-[color:var(--nx-text)]">{{ $brand->name }}</h3>
                        @if($brand->description)
                            <p class="mt-1 text-sm text-[color:var(--nx-muted)]">{{ $brand->description }}</p>
                        @endif
                        <div class="mt-3">
                            <x-nx-badge variant="neutral">{{ $boards->count() }} {{ $boards->count() === 1 ? 'Board' : 'Boards' }}</x-nx-badge>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @foreach($formats as $fmt)
                            <x-nx-button
                                :href="route('brands.export.download-brand', ['brandsBrand' => $brand->id, 'format' => $fmt['key']])"
                                :variant="$fmt['key'] === 'json' ? 'primary' : 'secondary'"
                            >
                                @if($fmt['key'] === 'json')
                                    @svg('heroicon-o-code-bracket', 'w-4 h-4')
                                @else
                                    @svg('heroicon-o-document', 'w-4 h-4')
                                @endif
                                {{ $fmt['label'] }}
                                @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                            </x-nx-button>
                        @endforeach
                    </div>
                </div>
            </x-nx-card>
        </x-nx-section>

        {{-- Einzelne Boards exportieren --}}
        <x-nx-section icon="heroicon-o-squares-2x2" title="Einzelne Boards exportieren" description="Wähle ein Board und ein Format für den Export">
            @if($boards->count() > 0)
                <x-nx-card flush class="overflow-hidden">
                    <div class="divide-y divide-[color:var(--nx-line)]">
                        @foreach($boards as $board)
                            <div class="flex items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-[color:var(--nx-hover)]">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[7px] bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">
                                        @switch($board['type'])
                                            @case('ci')
                                                @svg('heroicon-o-paint-brush', 'w-4 h-4')
                                                @break
                                            @case('content')
                                                @svg('heroicon-o-document-text', 'w-4 h-4')
                                                @break
                                            @case('social')
                                                @svg('heroicon-o-share', 'w-4 h-4')
                                                @break
                                            @case('kanban')
                                                @svg('heroicon-o-view-columns', 'w-4 h-4')
                                                @break
                                            @case('multi_content')
                                                @svg('heroicon-o-squares-2x2', 'w-4 h-4')
                                                @break
                                        @endswitch
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $board['name'] }}</h4>
                                        <span class="text-xs text-[color:var(--nx-faint)]">{{ $board['type_label'] }}</span>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    @foreach($formats as $fmt)
                                        <x-nx-button
                                            :href="route('brands.export.download-board', ['boardType' => $board['route_type'], 'boardId' => $board['id'], 'format' => $fmt['key']])"
                                            :variant="$fmt['key'] === 'json' ? 'primary' : 'secondary'"
                                            :title="$board['name'] . ' als ' . $fmt['label'] . ' exportieren'"
                                        >
                                            @if($fmt['key'] === 'json')
                                                @svg('heroicon-o-code-bracket', 'w-3.5 h-3.5')
                                            @else
                                                @svg('heroicon-o-document', 'w-3.5 h-3.5')
                                            @endif
                                            {{ $fmt['label'] }}
                                        </x-nx-button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-nx-card>
            @else
                <x-nx-empty icon="heroicon-o-squares-2x2">Keine Boards vorhanden – erstelle zuerst Boards in der Marken-Ansicht, um diese einzeln exportieren zu können.</x-nx-empty>
            @endif
        </x-nx-section>

        {{-- Export-Info --}}
        <x-nx-card>
            <div class="flex items-start gap-3">
                <div class="mt-0.5 shrink-0">
                    @svg('heroicon-o-information-circle', 'w-5 h-5 text-[color:var(--nx-faint)]')
                </div>
                <div>
                    <h4 class="mb-2 text-sm font-semibold text-[color:var(--nx-text)]">Hinweise zum Export</h4>
                    <ul class="space-y-1.5 text-sm text-[color:var(--nx-muted)]">
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded bg-[rgba(25,113,194,.12)] text-xs font-bold text-[color:var(--nx-info)]">J</span>
                            <span><strong>JSON</strong> – Maschinenlesbares Format mit allen Daten, Feldern und Medien-Referenzen. Geeignet für Backups, Datenübertragung und Weiterverarbeitung.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded bg-[rgba(224,49,49,.12)] text-xs font-bold text-[color:var(--nx-danger)]">P</span>
                            <span><strong>PDF</strong> – Formatiertes, druckfertiges Dokument im Brand Book-Stil. Berücksichtigt Markenfarben und ist nach Boards strukturiert.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </x-nx-card>
    </x-ui-page-container>
</x-ui-page>
