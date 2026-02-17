<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$competitorBoard->name" icon="heroicon-o-scale">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $competitorBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zur&uuml;ck zur Marke</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-100 to-orange-50 flex items-center justify-center">
                        @svg('heroicon-o-scale', 'w-6 h-6 text-orange-600')
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">{{ $competitorBoard->name }}</h1>
                        @if($competitorBoard->description)
                            <p class="text-[var(--ui-muted)] mt-1">{{ $competitorBoard->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Wettbewerber-Karten --}}
        <div>
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-100 to-orange-50 flex items-center justify-center">
                        @svg('heroicon-o-building-office', 'w-5 h-5 text-orange-600')
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Wettbewerber</h2>
                        <p class="text-sm text-[var(--ui-muted)]">Wettbewerber-Profile mit St&auml;rken und Schw&auml;chen</p>
                    </div>
                </div>
                @can('update', $competitorBoard)
                    <x-ui-button
                        variant="primary"
                        size="sm"
                        x-data
                        @click="$dispatch('open-modal-competitor', { competitorBoardId: {{ $competitorBoard->id }} })"
                    >
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Wettbewerber hinzuf&uuml;gen</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>

            @if($competitors->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($competitors as $competitor)
                        <div class="group relative bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-lg hover:border-orange-200 transition-all duration-200 overflow-hidden {{ $competitor->is_own_brand ? 'ring-2 ring-orange-300' : '' }}">
                            {{-- Competitor Header --}}
                            <div class="bg-gradient-to-br {{ $competitor->is_own_brand ? 'from-orange-50 to-amber-50' : 'from-slate-50 to-gray-50' }} p-6 pb-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-4">
                                        {{-- Logo/Avatar --}}
                                        @if($competitor->logo_url)
                                            <img src="{{ $competitor->logo_url }}" alt="{{ $competitor->name }}" class="w-14 h-14 rounded-xl object-contain bg-white border border-[var(--ui-border)]/40 shadow-sm flex-shrink-0">
                                        @else
                                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br {{ $competitor->is_own_brand ? 'from-orange-400 to-amber-500' : 'from-slate-400 to-gray-500' }} flex items-center justify-center text-white text-lg font-bold shadow-md flex-shrink-0">
                                                {{ strtoupper(substr($competitor->name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-lg font-bold text-[var(--ui-secondary)]">{{ $competitor->name }}</h3>
                                                @if($competitor->is_own_brand)
                                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 border border-orange-200">Eigene Marke</span>
                                                @endif
                                            </div>
                                            @if($competitor->website_url)
                                                <a href="{{ $competitor->website_url }}" target="_blank" rel="noopener" class="text-xs text-[var(--ui-muted)] hover:text-[var(--ui-primary)] flex items-center gap-1 mt-1">
                                                    @svg('heroicon-o-globe-alt', 'w-3 h-3')
                                                    {{ Str::limit(parse_url($competitor->website_url, PHP_URL_HOST) ?? $competitor->website_url, 30) }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    @can('update', $competitorBoard)
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                x-data
                                                @click="$dispatch('open-modal-competitor', { competitorBoardId: {{ $competitorBoard->id }}, competitorId: {{ $competitor->id }} })"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-white/80 rounded transition-colors"
                                                title="Bearbeiten"
                                            >
                                                @svg('heroicon-o-pencil', 'w-4 h-4')
                                            </button>
                                            <button
                                                wire:click="deleteCompetitor({{ $competitor->id }})"
                                                wire:confirm="Wettbewerber wirklich l&ouml;schen?"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-white/80 rounded transition-colors"
                                                title="L&ouml;schen"
                                            >
                                                @svg('heroicon-o-trash', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endcan
                                </div>
                            </div>

                            {{-- Competitor Body --}}
                            <div class="p-6 space-y-4">
                                {{-- Description --}}
                                @if($competitor->description)
                                    <p class="text-sm text-[var(--ui-secondary)] leading-relaxed">{{ Str::limit($competitor->description, 200) }}</p>
                                @endif

                                {{-- Strengths --}}
                                @if($competitor->strengths && count($competitor->strengths) > 0)
                                    <div>
                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-green-600 mb-2 flex items-center gap-1.5">
                                            @svg('heroicon-o-arrow-trending-up', 'w-3.5 h-3.5')
                                            St&auml;rken
                                        </h4>
                                        <div class="space-y-1">
                                            @foreach(array_slice($competitor->strengths, 0, 3) as $strength)
                                                <div class="flex items-start gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 mt-1.5 flex-shrink-0"></span>
                                                    <span class="text-xs text-[var(--ui-secondary)]">{{ $strength['text'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                            @if(count($competitor->strengths) > 3)
                                                <span class="text-[10px] text-[var(--ui-muted)]">+{{ count($competitor->strengths) - 3 }} weitere</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Weaknesses --}}
                                @if($competitor->weaknesses && count($competitor->weaknesses) > 0)
                                    <div>
                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-red-600 mb-2 flex items-center gap-1.5">
                                            @svg('heroicon-o-arrow-trending-down', 'w-3.5 h-3.5')
                                            Schw&auml;chen
                                        </h4>
                                        <div class="space-y-1">
                                            @foreach(array_slice($competitor->weaknesses, 0, 3) as $weakness)
                                                <div class="flex items-start gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400 mt-1.5 flex-shrink-0"></span>
                                                    <span class="text-xs text-[var(--ui-secondary)]">{{ $weakness['text'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                            @if(count($competitor->weaknesses) > 3)
                                                <span class="text-[10px] text-[var(--ui-muted)]">+{{ count($competitor->weaknesses) - 3 }} weitere</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Notes --}}
                                @if($competitor->notes)
                                    <div class="px-3 py-2 bg-amber-50 rounded-lg border border-amber-100">
                                        <h4 class="text-[10px] font-semibold uppercase tracking-wider text-amber-600 mb-1 flex items-center gap-1">
                                            @svg('heroicon-o-document-text', 'w-3 h-3')
                                            Notizen
                                        </h4>
                                        <p class="text-xs text-[var(--ui-secondary)] leading-relaxed">{{ Str::limit($competitor->notes, 150) }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-50 mb-4">
                        @svg('heroicon-o-building-office', 'w-8 h-8 text-orange-400')
                    </div>
                    <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Wettbewerber</p>
                    <p class="text-xs text-[var(--ui-muted)] mb-4">F&uuml;ge Wettbewerber hinzu, um die Marktpositionierung zu analysieren</p>
                    @can('update', $competitorBoard)
                        <x-ui-button
                            variant="primary"
                            size="sm"
                            x-data
                            @click="$dispatch('open-modal-competitor', { competitorBoardId: {{ $competitorBoard->id }} })"
                        >
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Wettbewerber hinzuf&uuml;gen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
            @endif
        </div>

        {{-- Positionierungsmatrix --}}
        @if($competitors->count() > 0)
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-100 to-indigo-50 flex items-center justify-center">
                        @svg('heroicon-o-chart-bar-square', 'w-5 h-5 text-indigo-600')
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Positionierungsmatrix</h2>
                        <p class="text-sm text-[var(--ui-muted)]">{{ $competitorBoard->axis_x_label }} vs. {{ $competitorBoard->axis_y_label }} &ndash; Markenpositionierung im Wettbewerbsumfeld</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm p-6 lg:p-8">
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
                                <span class="text-xs font-semibold text-indigo-600 writing-vertical" style="writing-mode: vertical-rl; transform: rotate(180deg);">{{ $competitorBoard->axis_y_label }}</span>
                            </div>
                            <div class="flex-1">
                                {{-- Axis Labels (top) --}}
                                <div class="flex justify-between mb-1 pl-1 pr-1">
                                    <span class="text-[10px] text-[var(--ui-muted)]">&nbsp;</span>
                                    <span class="text-[10px] font-medium text-indigo-500">{{ $competitorBoard->axis_y_max_label }}</span>
                                </div>

                                {{-- Grid Area --}}
                                <div x-ref="grid" class="relative aspect-square bg-gradient-to-br from-indigo-50/50 to-slate-50/50 border border-indigo-200/60 rounded-xl overflow-hidden select-none" style="cursor: crosshair;">
                                    {{-- Grid Lines --}}
                                    <div class="absolute inset-0">
                                        <div class="absolute left-1/2 top-0 bottom-0 border-l border-dashed border-indigo-200/80"></div>
                                        <div class="absolute top-1/2 left-0 right-0 border-t border-dashed border-indigo-200/80"></div>
                                        <div class="absolute left-1/4 top-0 bottom-0 border-l border-dashed border-indigo-100/60"></div>
                                        <div class="absolute left-3/4 top-0 bottom-0 border-l border-dashed border-indigo-100/60"></div>
                                        <div class="absolute top-1/4 left-0 right-0 border-t border-dashed border-indigo-100/60"></div>
                                        <div class="absolute top-3/4 left-0 right-0 border-t border-dashed border-indigo-100/60"></div>
                                    </div>

                                    {{-- Quadrant Labels --}}
                                    <div class="absolute top-2 left-3 text-[9px] font-medium text-indigo-300">{{ $competitorBoard->axis_x_min_label }} / {{ $competitorBoard->axis_y_max_label }}</div>
                                    <div class="absolute top-2 right-3 text-[9px] font-medium text-indigo-300">{{ $competitorBoard->axis_x_max_label }} / {{ $competitorBoard->axis_y_max_label }}</div>
                                    <div class="absolute bottom-2 left-3 text-[9px] font-medium text-indigo-300">{{ $competitorBoard->axis_x_min_label }} / {{ $competitorBoard->axis_y_min_label }}</div>
                                    <div class="absolute bottom-2 right-3 text-[9px] font-medium text-indigo-300">{{ $competitorBoard->axis_x_max_label }} / {{ $competitorBoard->axis_y_min_label }}</div>

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
                                                    <div class="w-8 h-8 rounded-full {{ $comp->is_own_brand ? 'bg-gradient-to-br from-orange-400 to-amber-500 ring-2 ring-orange-300 ring-offset-1' : 'bg-gradient-to-br from-slate-500 to-gray-600' }} flex items-center justify-center text-white text-[10px] font-bold shadow-lg hover:scale-110 transition-transform">
                                                        {{ strtoupper(substr($comp->name, 0, 2)) }}
                                                    </div>
                                                    <div class="absolute -bottom-5 left-1/2 -translate-x-1/2 whitespace-nowrap">
                                                        <span class="text-[9px] font-medium {{ $comp->is_own_brand ? 'text-orange-700' : 'text-[var(--ui-secondary)]' }} bg-white/90 px-1.5 py-0.5 rounded shadow-sm border border-[var(--ui-border)]/40">{{ Str::limit($comp->name, 15) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                {{-- Axis Labels (bottom) --}}
                                <div class="flex justify-between mt-1 pl-1 pr-1">
                                    <span class="text-[10px] font-medium text-indigo-500">{{ $competitorBoard->axis_y_min_label }}</span>
                                    <span class="text-[10px] text-[var(--ui-muted)]">&nbsp;</span>
                                </div>
                            </div>
                        </div>

                        {{-- X-Axis Label --}}
                        <div class="text-center mt-2 ml-20">
                            <div class="flex justify-between px-1">
                                <span class="text-[10px] font-medium text-indigo-500">{{ $competitorBoard->axis_x_min_label }}</span>
                                <span class="text-xs font-semibold text-indigo-600">{{ $competitorBoard->axis_x_label }}</span>
                                <span class="text-[10px] font-medium text-indigo-500">{{ $competitorBoard->axis_x_max_label }}</span>
                            </div>
                        </div>

                        {{-- Legend --}}
                        <div class="flex flex-wrap items-center gap-4 mt-6 pt-4 border-t border-[var(--ui-border)]/40">
                            @foreach($competitors as $comp)
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-full {{ $comp->is_own_brand ? 'bg-gradient-to-br from-orange-400 to-amber-500' : 'bg-gradient-to-br from-slate-500 to-gray-600' }} flex items-center justify-center">
                                        <span class="text-[7px] text-white font-bold">{{ strtoupper(substr($comp->name, 0, 1)) }}</span>
                                    </div>
                                    <span class="text-xs {{ $comp->is_own_brand ? 'font-semibold text-orange-700' : 'text-[var(--ui-secondary)]' }}">{{ $comp->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Differenzierungs-Tabelle --}}
        @php
            $competitorsWithDiff = $competitors->filter(fn($c) => $c->differentiation && count($c->differentiation) > 0);
        @endphp
        @if($competitorsWithDiff->count() > 0 && $ownBrand)
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center">
                        @svg('heroicon-o-table-cells', 'w-5 h-5 text-emerald-600')
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Differenzierung</h2>
                        <p class="text-sm text-[var(--ui-muted)]">Spaltenvergleich: Eigene Marke vs. Wettbewerber</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gradient-to-r from-emerald-50 to-teal-50">
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-emerald-700 border-b border-emerald-200/60">Merkmal</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-orange-700 border-b border-emerald-200/60 bg-orange-50/50">
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center">
                                                <span class="text-[8px] text-white font-bold">{{ strtoupper(substr($ownBrand->name, 0, 1)) }}</span>
                                            </div>
                                            {{ $ownBrand->name }}
                                        </div>
                                    </th>
                                    @foreach($competitorsWithDiff->where('is_own_brand', false) as $comp)
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 border-b border-emerald-200/60">
                                            <div class="flex items-center gap-2">
                                                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-slate-400 to-gray-500 flex items-center justify-center">
                                                    <span class="text-[8px] text-white font-bold">{{ strtoupper(substr($comp->name, 0, 1)) }}</span>
                                                </div>
                                                {{ $comp->name }}
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--ui-border)]/40">
                                @php
                                    // Collect all unique categories from own brand differentiation
                                    $categories = collect($ownBrand->differentiation ?? [])->pluck('category')->unique()->values();
                                @endphp
                                @foreach($categories as $category)
                                    <tr class="hover:bg-[var(--ui-muted-5)] transition-colors">
                                        <td class="px-6 py-3 font-medium text-[var(--ui-secondary)]">{{ $category }}</td>
                                        <td class="px-6 py-3 text-[var(--ui-secondary)] bg-orange-50/30">
                                            @php
                                                $ownDiff = collect($ownBrand->differentiation)->firstWhere('category', $category);
                                            @endphp
                                            {{ $ownDiff['own_value'] ?? '-' }}
                                        </td>
                                        @foreach($competitorsWithDiff->where('is_own_brand', false) as $comp)
                                            <td class="px-6 py-3 text-[var(--ui-secondary)]">
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
                </div>
            </div>
        @elseif($competitors->count() > 0)
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center">
                        @svg('heroicon-o-table-cells', 'w-5 h-5 text-emerald-600')
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Differenzierung</h2>
                        <p class="text-sm text-[var(--ui-muted)]">Spaltenvergleich: Eigene Marke vs. Wettbewerber</p>
                    </div>
                </div>
                <div class="text-center py-12 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-50 mb-3">
                        @svg('heroicon-o-table-cells', 'w-6 h-6 text-emerald-400')
                    </div>
                    <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Differenzierungsdaten</p>
                    <p class="text-xs text-[var(--ui-muted)]">Markiere einen Wettbewerber als &bdquo;Eigene Marke&ldquo; und f&uuml;ge Differenzierungsmerkmale hinzu.</p>
                </div>
            </div>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-&Uuml;bersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $competitorBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zur&uuml;ck zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $competitorBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-competitor', { competitorBoardId: {{ $competitorBoard->id }} })"
                                class="w-full"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Wettbewerber hinzuf&uuml;gen</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-competitor-board-settings', { competitorBoardId: {{ $competitorBoard->id }} })" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                    <span>Einstellungen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                </div>

                {{-- Board-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Typ</span>
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-orange-50 text-orange-600 border border-orange-200">
                                Wettbewerber
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $competitorBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($competitors->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Wettbewerber</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $competitors->count() }}
                                </span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">X-Achse</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $competitorBoard->axis_x_label }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Y-Achse</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $competitorBoard->axis_y_label }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivit&auml;ten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Letzte Aktivit&auml;ten</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                            <div class="text-sm font-medium text-[var(--ui-secondary)]">{{ $activity['title'] ?? 'Aktivit&auml;t' }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $activity['time'] ?? '' }}</div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                                @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivit&auml;ten</p>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">&Auml;nderungen werden hier angezeigt</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <livewire:brands.competitor-board-settings-modal />
    <livewire:brands.competitor-modal />
</x-ui-page>
