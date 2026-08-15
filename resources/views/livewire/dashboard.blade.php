<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Marken" icon="heroicon-o-tag" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'icon' => 'tag'],
        ]">
            <x-nx-button variant="primary" size="sm" wire:click="createBrand">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neue Marke</span>
            </x-nx-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-6">

        {{-- Kennzahlen --}}
        <x-nx-stat-grid cols="4">
            <x-nx-stat label="Marken" :value="$activeCount" hint="aktiv" icon="heroicon-o-tag" />
            <x-nx-stat label="Boards" :value="$totalBoards" hint="gesamt" icon="heroicon-o-squares-2x2" />
            <x-nx-stat label="Verortet" :value="$linkedCount" :hint="'von ' . $totalBrands . ' Marken'" icon="heroicon-o-building-office-2" />
            <x-nx-stat label="Archiviert" :value="$archivedCount" hint="abgeschlossen" icon="heroicon-o-archive-box" />
        </x-nx-stat-grid>

        {{-- Marken-Tabelle --}}
        @if($rows->isNotEmpty())
            <x-nx-card flush>
                <x-nx-table>
                    <x-nx-table-header>
                        <x-nx-table-header-cell sortable sortField="name" :currentSort="$sortField" :sortDirection="$sortDirection">Marke</x-nx-table-header-cell>
                        <x-nx-table-header-cell sortable sortField="verortung" :currentSort="$sortField" :sortDirection="$sortDirection">Verortung</x-nx-table-header-cell>
                        <x-nx-table-header-cell>Farben</x-nx-table-header-cell>
                        <x-nx-table-header-cell sortable sortField="boards" :currentSort="$sortField" :sortDirection="$sortDirection" align="right">Boards</x-nx-table-header-cell>
                        <x-nx-table-header-cell sortable sortField="status" :currentSort="$sortField" :sortDirection="$sortDirection" align="center">Status</x-nx-table-header-cell>
                        <x-nx-table-header-cell sortable sortField="updated" :currentSort="$sortField" :sortDirection="$sortDirection" align="right">Zuletzt</x-nx-table-header-cell>
                        <x-nx-table-header-cell align="right"></x-nx-table-header-cell>
                    </x-nx-table-header>
                    <x-nx-table-body>
                        @foreach($rows as $row)
                            @php($brand = $row['brand'])
                            @php($ci = $row['ciBoard'])
                            <x-nx-table-row>
                                {{-- Marke --}}
                                <x-nx-table-cell>
                                    <a href="{{ route('brands.brands.show', $brand) }}" wire:navigate class="group flex items-center gap-3">
                                        {{-- Farb-Punkt / Initial --}}
                                        @if($ci && $ci->primary_color)
                                            <span class="h-8 w-8 shrink-0 rounded-[6px] ring-1 ring-[color:var(--nx-line)]" style="background-color: {{ $ci->primary_color }};"></span>
                                        @else
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] bg-[color:var(--nx-accent-soft)] text-xs font-semibold text-[color:var(--nx-muted)]">{{ mb_strtoupper(mb_substr($brand->name, 0, 1)) }}</span>
                                        @endif
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium text-[color:var(--nx-text)] group-hover:underline">{{ $brand->name }}</span>
                                            @if($ci && ($ci->slogan || $ci->tagline))
                                                <span class="block truncate text-xs italic text-[color:var(--nx-faint)]">{{ Str::limit($ci->slogan ?: $ci->tagline, 48) }}</span>
                                            @elseif($brand->description)
                                                <span class="block truncate text-xs text-[color:var(--nx-faint)]">{{ Str::limit($brand->description, 48) }}</span>
                                            @endif
                                        </span>
                                    </a>
                                </x-nx-table-cell>

                                {{-- Verortung --}}
                                <x-nx-table-cell>
                                    @if($row['verortungEntity'])
                                        <span class="block truncate text-[color:var(--nx-text)]">{{ $row['verortungEntity'] }}</span>
                                        @if($row['verortungType'])
                                            <span class="block truncate text-xs text-[color:var(--nx-faint)]">{{ $row['verortungType'] }}</span>
                                        @endif
                                    @else
                                        <span class="text-[color:var(--nx-faint)]">Unverknüpft</span>
                                    @endif
                                </x-nx-table-cell>

                                {{-- Farben --}}
                                <x-nx-table-cell>
                                    @if($ci)
                                        <span class="flex items-center">
                                            @foreach(collect([$ci->primary_color, $ci->secondary_color, $ci->accent_color])->filter() as $c)
                                                <span class="h-5 w-5 rounded-full ring-2 ring-[color:var(--nx-surface)] -ml-1.5 first:ml-0" style="background-color: {{ $c }};"></span>
                                            @endforeach
                                            @foreach($ci->colors->take(3) as $color)
                                                <span class="h-5 w-5 rounded-full ring-2 ring-[color:var(--nx-surface)] -ml-1.5" style="background-color: {{ $color->color }};"></span>
                                            @endforeach
                                        </span>
                                    @else
                                        <span class="text-[color:var(--nx-faint)]">—</span>
                                    @endif
                                </x-nx-table-cell>

                                {{-- Boards --}}
                                <x-nx-table-cell align="right">
                                    <span class="tabular-nums {{ $row['boardCount'] === 0 ? 'text-[color:var(--nx-faint)]' : 'text-[color:var(--nx-text)]' }}">{{ $row['boardCount'] }}</span>
                                </x-nx-table-cell>

                                {{-- Status --}}
                                <x-nx-table-cell align="center">
                                    @if($row['done'])
                                        <x-nx-badge variant="neutral">Archiviert</x-nx-badge>
                                    @else
                                        <x-nx-badge variant="success" dot>Aktiv</x-nx-badge>
                                    @endif
                                </x-nx-table-cell>

                                {{-- Zuletzt --}}
                                <x-nx-table-cell align="right">
                                    <span class="whitespace-nowrap text-[color:var(--nx-muted)]">{{ optional($brand->updated_at)->format('d.m.Y') }}</span>
                                </x-nx-table-cell>

                                {{-- Aktionen --}}
                                <x-nx-table-cell align="right">
                                    <x-nx-dropdown>
                                        <x-nx-dropdown-item :href="route('brands.brands.show', $brand)">
                                            @svg('heroicon-o-eye', 'w-4 h-4') Öffnen
                                        </x-nx-dropdown-item>
                                        <x-nx-dropdown-item :href="route('brands.export.show', $brand)">
                                            @svg('heroicon-o-arrow-down-tray', 'w-4 h-4') Export
                                        </x-nx-dropdown-item>
                                    </x-nx-dropdown>
                                </x-nx-table-cell>
                            </x-nx-table-row>
                        @endforeach
                    </x-nx-table-body>
                </x-nx-table>
            </x-nx-card>
        @else
            {{-- Leerzustand --}}
            <x-nx-card>
                <x-nx-empty icon="heroicon-o-tag">
                    Noch keine Marken – erstelle deine erste Marke, um loszulegen.
                    <x-slot name="action">
                        <x-nx-button variant="primary" wire:click="createBrand">
                            @svg('heroicon-o-plus', 'w-4 h-4') Neue Marke
                        </x-nx-button>
                    </x-slot>
                </x-nx-empty>
            </x-nx-card>
        @endif

    </x-ui-page-container>
</x-ui-page>
