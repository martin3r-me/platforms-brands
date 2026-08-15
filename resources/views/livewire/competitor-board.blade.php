<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$competitorBoard->name" icon="heroicon-o-scale" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $competitorBoard->brand->name, 'href' => route('brands.brands.show', $competitorBoard->brand)],
            ['label' => $competitorBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $competitorBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-competitor-board-settings', { competitorBoardId: {{ $competitorBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
            </x-slot>

            @can('update', $competitorBoard)
                <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-competitor', { competitorBoardId: {{ $competitorBoard->id }} })">
                    @svg('heroicon-o-plus', 'w-4 h-4') Wettbewerber hinzufügen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $competitorBoard->name }}</h1>
            @if($competitorBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $competitorBoard->description }}</p>
            @endif
        </div>

        {{-- Wettbewerber-Karten --}}
        <x-nx-section icon="heroicon-o-building-office" title="Wettbewerber" :hint="(string) $competitors->count()" description="Wettbewerber-Profile mit Stärken und Schwächen">
            @can('update', $competitorBoard)
                <x-slot name="action">
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-competitor', { competitorBoardId: {{ $competitorBoard->id }} })">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5') Wettbewerber
                    </x-nx-button>
                </x-slot>
            @endcan

            @if($competitors->count() > 0)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($competitors as $competitor)
                        <x-nx-card hover class="group flex flex-col {{ $competitor->is_own_brand ? 'ring-1 ring-[color:var(--nx-accent)]' : '' }}">
                            {{-- Kopf: Identität --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    @if($competitor->logo_url)
                                        <img src="{{ $competitor->logo_url }}" alt="{{ $competitor->name }}" class="h-11 w-11 shrink-0 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] object-contain">
                                    @else
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[8px] bg-[color:var(--nx-accent-soft)] text-sm font-semibold text-[color:var(--nx-text)]">
                                            {{ strtoupper(substr($competitor->name, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="truncate text-[15px] font-semibold text-[color:var(--nx-text)]">{{ $competitor->name }}</h3>
                                            @if($competitor->is_own_brand)
                                                <x-nx-badge variant="accent">Eigene Marke</x-nx-badge>
                                            @endif
                                        </div>
                                        @if($competitor->website_url)
                                            <a href="{{ $competitor->website_url }}" target="_blank" rel="noopener" class="mt-0.5 flex items-center gap-1 text-[13px] text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-accent)]">
                                                @svg('heroicon-o-globe-alt', 'w-3.5 h-3.5 shrink-0')
                                                <span class="truncate">{{ Str::limit(parse_url($competitor->website_url, PHP_URL_HOST) ?? $competitor->website_url, 30) }}</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                @can('update', $competitorBoard)
                                    <div class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button type="button" x-data @click="$dispatch('open-modal-competitor', { competitorBoardId: {{ $competitorBoard->id }}, competitorId: {{ $competitor->id }} })"
                                                class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]" title="Bearbeiten">
                                            @svg('heroicon-o-pencil', 'w-4 h-4')
                                        </button>
                                        <button type="button" wire:click="deleteCompetitor({{ $competitor->id }})" wire:confirm="Wettbewerber wirklich löschen?"
                                                class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-danger)]" title="Löschen">
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                        </button>
                                    </div>
                                @endcan
                            </div>

                            <div class="mt-4 space-y-4">
                                {{-- Beschreibung --}}
                                @if($competitor->description)
                                    <p class="text-[13px] leading-relaxed text-[color:var(--nx-text)]">{{ Str::limit($competitor->description, 200) }}</p>
                                @endif

                                {{-- Stärken --}}
                                @if($competitor->strengths && count($competitor->strengths) > 0)
                                    <div>
                                        <h4 class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Stärken</h4>
                                        <div class="space-y-1">
                                            @foreach(array_slice($competitor->strengths, 0, 3) as $strength)
                                                <div class="flex items-start gap-2">
                                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-success)]"></span>
                                                    <span class="text-[13px] text-[color:var(--nx-text)]">{{ $strength['text'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                            @if(count($competitor->strengths) > 3)
                                                <span class="text-[11px] text-[color:var(--nx-faint)]">+{{ count($competitor->strengths) - 3 }} weitere</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Schwächen --}}
                                @if($competitor->weaknesses && count($competitor->weaknesses) > 0)
                                    <div>
                                        <h4 class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Schwächen</h4>
                                        <div class="space-y-1">
                                            @foreach(array_slice($competitor->weaknesses, 0, 3) as $weakness)
                                                <div class="flex items-start gap-2">
                                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-danger)]"></span>
                                                    <span class="text-[13px] text-[color:var(--nx-text)]">{{ $weakness['text'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                            @if(count($competitor->weaknesses) > 3)
                                                <span class="text-[11px] text-[color:var(--nx-faint)]">+{{ count($competitor->weaknesses) - 3 }} weitere</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Notizen --}}
                                @if($competitor->notes)
                                    <div class="rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2">
                                        <h4 class="mb-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Notizen</h4>
                                        <p class="text-[13px] leading-relaxed text-[color:var(--nx-muted)]">{{ Str::limit($competitor->notes, 150) }}</p>
                                    </div>
                                @endif
                            </div>
                        </x-nx-card>
                    @endforeach
                </div>
            @else
                <x-nx-empty icon="heroicon-o-building-office">
                    Noch keine Wettbewerber – füge Wettbewerber hinzu, um die Marktpositionierung zu analysieren.
                    @can('update', $competitorBoard)
                        <x-slot name="action">
                            <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-competitor', { competitorBoardId: {{ $competitorBoard->id }} })">
                                @svg('heroicon-o-plus', 'w-4 h-4') Wettbewerber hinzufügen
                            </x-nx-button>
                        </x-slot>
                    @endcan
                </x-nx-empty>
            @endif
        </x-nx-section>

        {{-- Positionierungsmatrix --}}
        @if($competitors->count() > 0)
            <x-nx-section icon="heroicon-o-chart-bar-square" title="Positionierungsmatrix" :description="$competitorBoard->axis_x_label . ' vs. ' . $competitorBoard->axis_y_label . ' – Markenpositionierung im Wettbewerbsumfeld'">
                <x-nx-card>
                    <div class="relative" x-data="{
                        dragging: null,
                        startX: 0, startY: 0,
                        startPosX: 0, startPosY: 0,

                        onMouseDown(e, id, posX, posY) {
                            this.dragging = id;
                            this.startX = e.clientX;
                            this.startY = e.clientY;
                            this.startPosX = posX;
                            this.startPosY = posY;
                            e.preventDefault();
                        },
                        onMouseMove(e) {
                            if (!this.dragging) return;
                            const grid = this.$refs.grid;
                            const rect = grid.getBoundingClientRect();
                            const x = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
                            const y = Math.max(0, Math.min(100, 100 - ((e.clientY - rect.top) / rect.height) * 100));
                            const dot = document.getElementById('dot-' + this.dragging);
                            if (dot) {
                                dot.style.left = x + '%';
                                dot.style.bottom = y + '%';
                            }
                        },
                        onMouseUp(e) {
                            if (!this.dragging) return;
                            const grid = this.$refs.grid;
                            const rect = grid.getBoundingClientRect();
                            const x = Math.round(Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100)));
                            const y = Math.round(Math.max(0, Math.min(100, 100 - ((e.clientY - rect.top) / rect.height) * 100)));
                            $wire.updateCompetitorPosition(this.dragging, x, y);
                            this.dragging = null;
                        }
                    }" @mousemove.window="onMouseMove($event)" @mouseup.window="onMouseUp($event)">
                        {{-- Y-Axis Label --}}
                        <div class="flex items-center">
                            <div class="w-20 flex-shrink-0 text-center">
                                <span class="text-xs font-semibold text-[color:var(--nx-muted)] writing-vertical" style="writing-mode: vertical-rl; transform: rotate(180deg);">{{ $competitorBoard->axis_y_label }}</span>
                            </div>
                            <div class="flex-1">
                                {{-- Axis Labels (top) --}}
                                <div class="flex justify-between mb-1 pl-1 pr-1">
                                    <span class="text-[10px] text-[color:var(--nx-faint)]">&nbsp;</span>
                                    <span class="text-[10px] font-medium text-[color:var(--nx-muted)]">{{ $competitorBoard->axis_y_max_label }}</span>
                                </div>

                                {{-- Grid Area --}}
                                <div x-ref="grid" class="relative aspect-square rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] overflow-hidden select-none" style="cursor: crosshair;">
                                    {{-- Grid Lines --}}
                                    <div class="absolute inset-0">
                                        <div class="absolute left-1/2 top-0 bottom-0 border-l border-dashed border-[color:var(--nx-line-strong)]"></div>
                                        <div class="absolute top-1/2 left-0 right-0 border-t border-dashed border-[color:var(--nx-line-strong)]"></div>
                                        <div class="absolute left-1/4 top-0 bottom-0 border-l border-dashed border-[color:var(--nx-line)]"></div>
                                        <div class="absolute left-3/4 top-0 bottom-0 border-l border-dashed border-[color:var(--nx-line)]"></div>
                                        <div class="absolute top-1/4 left-0 right-0 border-t border-dashed border-[color:var(--nx-line)]"></div>
                                        <div class="absolute top-3/4 left-0 right-0 border-t border-dashed border-[color:var(--nx-line)]"></div>
                                    </div>

                                    {{-- Quadrant Labels --}}
                                    <div class="absolute top-2 left-3 text-[9px] font-medium text-[color:var(--nx-faint)]">{{ $competitorBoard->axis_x_min_label }} / {{ $competitorBoard->axis_y_max_label }}</div>
                                    <div class="absolute top-2 right-3 text-[9px] font-medium text-[color:var(--nx-faint)]">{{ $competitorBoard->axis_x_max_label }} / {{ $competitorBoard->axis_y_max_label }}</div>
                                    <div class="absolute bottom-2 left-3 text-[9px] font-medium text-[color:var(--nx-faint)]">{{ $competitorBoard->axis_x_min_label }} / {{ $competitorBoard->axis_y_min_label }}</div>
                                    <div class="absolute bottom-2 right-3 text-[9px] font-medium text-[color:var(--nx-faint)]">{{ $competitorBoard->axis_x_max_label }} / {{ $competitorBoard->axis_y_min_label }}</div>

                                    {{-- Dots (Markers) --}}
                                    @foreach($competitors as $comp)
                                        @if($comp->position_x !== null && $comp->position_y !== null)
                                            <div
                                                id="dot-{{ $comp->id }}"
                                                class="absolute transform -translate-x-1/2 translate-y-1/2 z-10 group/dot"
                                                style="left: {{ $comp->position_x }}%; bottom: {{ $comp->position_y }}%;"
                                                @can('update', $competitorBoard)
                                                    @mousedown="onMouseDown($event, {{ $comp->id }}, {{ $comp->position_x }}, {{ $comp->position_y }})"
                                                    style="left: {{ $comp->position_x }}%; bottom: {{ $comp->position_y }}%; cursor: grab;"
                                                @endcan
                                            >
                                                <div class="relative">
                                                    <div class="flex h-8 w-8 items-center justify-center rounded-full text-[10px] font-bold transition-transform hover:scale-110 {{ $comp->is_own_brand ? 'bg-[color:var(--nx-accent)] text-[color:var(--nx-on-accent)] ring-1 ring-[color:var(--nx-accent)] ring-offset-1' : 'bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-text)]' }}">
                                                        {{ strtoupper(substr($comp->name, 0, 2)) }}
                                                    </div>
                                                    <div class="absolute -bottom-5 left-1/2 -translate-x-1/2 whitespace-nowrap">
                                                        <span class="rounded border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-1.5 py-0.5 text-[9px] font-medium {{ $comp->is_own_brand ? 'text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)]' }}">{{ Str::limit($comp->name, 15) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                {{-- Axis Labels (bottom) --}}
                                <div class="flex justify-between mt-1 pl-1 pr-1">
                                    <span class="text-[10px] font-medium text-[color:var(--nx-muted)]">{{ $competitorBoard->axis_y_min_label }}</span>
                                    <span class="text-[10px] text-[color:var(--nx-faint)]">&nbsp;</span>
                                </div>
                            </div>
                        </div>

                        {{-- X-Axis Label --}}
                        <div class="text-center mt-2 ml-20">
                            <div class="flex justify-between px-1">
                                <span class="text-[10px] font-medium text-[color:var(--nx-muted)]">{{ $competitorBoard->axis_x_min_label }}</span>
                                <span class="text-xs font-semibold text-[color:var(--nx-text)]">{{ $competitorBoard->axis_x_label }}</span>
                                <span class="text-[10px] font-medium text-[color:var(--nx-muted)]">{{ $competitorBoard->axis_x_max_label }}</span>
                            </div>
                        </div>

                        {{-- Legend --}}
                        <div class="flex flex-wrap items-center gap-4 mt-6 pt-4 border-t border-[color:var(--nx-line)]">
                            @foreach($competitors as $comp)
                                <div class="flex items-center gap-2">
                                    <div class="flex h-4 w-4 items-center justify-center rounded-full {{ $comp->is_own_brand ? 'bg-[color:var(--nx-accent)] text-[color:var(--nx-on-accent)]' : 'bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-text)]' }}">
                                        <span class="text-[7px] font-bold">{{ strtoupper(substr($comp->name, 0, 1)) }}</span>
                                    </div>
                                    <span class="text-xs {{ $comp->is_own_brand ? 'font-semibold text-[color:var(--nx-text)]' : 'text-[color:var(--nx-muted)]' }}">{{ $comp->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-nx-card>
            </x-nx-section>
        @endif

        {{-- Differenzierungs-Tabelle --}}
        @php
            $competitorsWithDiff = $competitors->filter(fn($c) => $c->differentiation && count($c->differentiation) > 0);
        @endphp
        @if($competitorsWithDiff->count() > 0 && $ownBrand)
            <x-nx-section icon="heroicon-o-table-cells" title="Differenzierung" description="Spaltenvergleich: Eigene Marke vs. Wettbewerber">
                <x-nx-card flush class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[color:var(--nx-line)]">
                                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Merkmal</th>
                                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-5 w-5 items-center justify-center rounded-full bg-[color:var(--nx-accent)] text-[color:var(--nx-on-accent)]">
                                                <span class="text-[8px] font-bold">{{ strtoupper(substr($ownBrand->name, 0, 1)) }}</span>
                                            </div>
                                            {{ $ownBrand->name }}
                                        </div>
                                    </th>
                                    @foreach($competitorsWithDiff->where('is_own_brand', false) as $comp)
                                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">
                                            <div class="flex items-center gap-2">
                                                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-text)]">
                                                    <span class="text-[8px] font-bold">{{ strtoupper(substr($comp->name, 0, 1)) }}</span>
                                                </div>
                                                {{ $comp->name }}
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[color:var(--nx-line)]">
                                @php
                                    // Collect all unique categories from own brand differentiation
                                    $categories = collect($ownBrand->differentiation ?? [])->pluck('category')->unique()->values();
                                @endphp
                                @foreach($categories as $category)
                                    <tr class="transition-colors hover:bg-[color:var(--nx-hover)]">
                                        <td class="px-5 py-3 font-medium text-[color:var(--nx-text)]">{{ $category }}</td>
                                        <td class="px-5 py-3 text-[color:var(--nx-text)]">
                                            @php
                                                $ownDiff = collect($ownBrand->differentiation)->firstWhere('category', $category);
                                            @endphp
                                            {{ $ownDiff['own_value'] ?? '-' }}
                                        </td>
                                        @foreach($competitorsWithDiff->where('is_own_brand', false) as $comp)
                                            <td class="px-5 py-3 text-[color:var(--nx-muted)]">
                                                @php
                                                    $compDiff = collect($comp->differentiation)->firstWhere('category', $category);
                                                @endphp
                                                {{ $compDiff['competitor_value'] ?? '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-nx-card>
            </x-nx-section>
        @elseif($competitors->count() > 0)
            <x-nx-section icon="heroicon-o-table-cells" title="Differenzierung" description="Spaltenvergleich: Eigene Marke vs. Wettbewerber">
                <x-nx-empty icon="heroicon-o-table-cells">
                    Noch keine Differenzierungsdaten – markiere einen Wettbewerber als „Eigene Marke" und füge Differenzierungsmerkmale hinzu.
                </x-nx-empty>
            </x-nx-section>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        @include('brands::partials.board-sidebar', ['detailRows' => [
            ['label' => 'Typ', 'value' => 'Wettbewerber'],
            ['label' => 'Erstellt', 'value' => $competitorBoard->created_at->format('d.m.Y')],
            ['label' => 'Wettbewerber', 'value' => (string) $competitors->count()],
            ['label' => 'X-Achse', 'value' => $competitorBoard->axis_x_label],
            ['label' => 'Y-Achse', 'value' => $competitorBoard->axis_y_label],
        ]])
    </x-slot>

    <x-slot name="activity">
        @include('brands::partials.board-activity')
    </x-slot>

    <livewire:brands.competitor-board-settings-modal />
    <livewire:brands.competitor-modal />
</x-ui-page>
