@php
    $cluster = $data['cluster'];
    $keywords = $data['keywords'];
    $score = $data['opportunity_score'];
    $clusterColor = $cluster->color ?? 'gray';

    // Score color
    if ($score <= 25) { $scoreColor = 'red'; }
    elseif ($score <= 50) { $scoreColor = 'orange'; }
    elseif ($score <= 75) { $scoreColor = 'yellow'; }
    else { $scoreColor = 'emerald'; }

    // KD color
    $kd = $data['weighted_kd'];
    if ($kd <= 20) { $kdColor = 'emerald'; }
    elseif ($kd <= 40) { $kdColor = 'green'; }
    elseif ($kd <= 60) { $kdColor = 'yellow'; }
    elseif ($kd <= 80) { $kdColor = 'orange'; }
    else { $kdColor = 'red'; }

    // Position color
    $pos = $data['avg_position'];
    $posColor = $pos !== null
        ? ($pos <= 3 ? 'emerald' : ($pos <= 10 ? 'lime' : ($pos <= 20 ? 'yellow' : ($pos <= 50 ? 'orange' : 'red'))))
        : 'gray';
@endphp

<div x-data="{ expanded: false }" class="group">
    {{-- Cluster Card --}}
    <div @click="expanded = !expanded"
         class="relative cursor-pointer rounded-xl border bg-white transition-all duration-200
                hover:shadow-md hover:border-{{ $clusterColor }}-300
                border-[var(--ui-border)]/60"
         :class="expanded ? 'shadow-md border-{{ $clusterColor }}-300 ring-1 ring-{{ $clusterColor }}-100' : ''">

        {{-- Color accent bar --}}
        <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl bg-{{ $clusterColor }}-500"></div>

        <div class="pl-5 pr-4 py-3">
            {{-- Desktop: single row --}}
            <div class="hidden lg:flex items-center gap-4">
                {{-- Expand icon --}}
                <div class="flex-shrink-0 flex items-center justify-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg transition-all duration-200 group-hover:bg-{{ $clusterColor }}-50"
                          :class="expanded ? 'bg-{{ $clusterColor }}-50 rotate-90' : ''">
                        @svg('heroicon-o-chevron-right', 'w-3.5 h-3.5 text-[var(--ui-muted)] transition-transform')
                    </span>
                </div>

                {{-- Cluster name --}}
                <div class="flex-1 min-w-0 flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-{{ $clusterColor }}-500 flex-shrink-0 ring-2 ring-{{ $clusterColor }}-500/20"></span>
                    <span class="text-sm font-semibold text-[var(--ui-secondary)] truncate">{{ $cluster->name }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-{{ $clusterColor }}-50 text-{{ $clusterColor }}-700 tabular-nums flex-shrink-0">
                        {{ $data['count'] }} KW
                    </span>
                </div>

                {{-- Metrics row --}}
                <div class="flex items-center gap-5 flex-shrink-0">
                    {{-- Score --}}
                    <div class="flex items-center gap-2 w-28">
                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-{{ $scoreColor }}-500 transition-all" style="width: {{ $score }}%"></div>
                        </div>
                        <span class="text-xs font-bold text-{{ $scoreColor }}-700 tabular-nums w-7 text-right">{{ $score }}</span>
                    </div>

                    {{-- SV --}}
                    <div class="w-16 text-right">
                        <span class="text-xs font-semibold text-[var(--ui-secondary)] tabular-nums">{{ number_format($data['sum_sv']) }}</span>
                    </div>

                    {{-- KD --}}
                    <div class="w-12 flex justify-end">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-{{ $kdColor }}-50 text-{{ $kdColor }}-700 border border-{{ $kdColor }}-200 tabular-nums">
                            {{ $data['weighted_kd'] }}
                        </span>
                    </div>

                    {{-- Traffic-Wert --}}
                    <div class="w-16 text-right">
                        <span class="text-xs font-semibold text-emerald-700 tabular-nums">{{ number_format($data['traffic_value'], 0) }} {{ "\u{20AC}" }}</span>
                    </div>

                    {{-- Coverage --}}
                    <div class="w-24 flex items-center justify-end gap-1.5">
                        <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-lime-500 transition-all" style="width: {{ $data['coverage'] }}%"></div>
                        </div>
                        <span class="text-[11px] text-[var(--ui-muted)] tabular-nums w-8 text-right">{{ $data['coverage'] }}%</span>
                    </div>

                    {{-- Position --}}
                    <div class="w-12 flex justify-end">
                        @if($pos !== null)
                            <span class="inline-flex items-center justify-center min-w-[28px] h-5 px-1.5 rounded text-[11px] font-bold bg-{{ $posColor }}-50 text-{{ $posColor }}-700 border border-{{ $posColor }}-200 tabular-nums">
                                {{ $data['avg_position'] }}
                            </span>
                        @else
                            <span class="text-xs text-[var(--ui-muted)]">{{ "\u{2013}" }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Mobile/Tablet: stacked layout --}}
            <div class="lg:hidden">
                {{-- Header row --}}
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg transition-all duration-200"
                          :class="expanded ? 'bg-{{ $clusterColor }}-50 rotate-90' : ''">
                        @svg('heroicon-o-chevron-right', 'w-3.5 h-3.5 text-[var(--ui-muted)]')
                    </span>
                    <span class="w-2.5 h-2.5 rounded-full bg-{{ $clusterColor }}-500 flex-shrink-0 ring-2 ring-{{ $clusterColor }}-500/20"></span>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-semibold text-[var(--ui-secondary)] truncate block">{{ $cluster->name }}</span>
                    </div>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-{{ $clusterColor }}-50 text-{{ $clusterColor }}-700 tabular-nums">
                        {{ $data['count'] }}
                    </span>
                </div>

                {{-- Metrics grid --}}
                <div class="mt-2.5 ml-9 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2">
                    <div>
                        <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Score</div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <div class="w-10 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-{{ $scoreColor }}-500" style="width: {{ $score }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-{{ $scoreColor }}-700 tabular-nums">{{ $score }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">SV</div>
                        <div class="text-xs font-semibold text-[var(--ui-secondary)] tabular-nums mt-0.5">{{ number_format($data['sum_sv']) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">KD</div>
                        <div class="mt-0.5">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-{{ $kdColor }}-50 text-{{ $kdColor }}-700 border border-{{ $kdColor }}-200 tabular-nums">{{ $data['weighted_kd'] }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Wert</div>
                        <div class="text-xs font-semibold text-emerald-700 tabular-nums mt-0.5">{{ number_format($data['traffic_value'], 0) }} {{ "\u{20AC}" }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Expanded: Keyword list --}}
    <div x-show="expanded" x-collapse.duration.200ms>
        <div class="mt-1 rounded-xl border border-{{ $clusterColor }}-200/60 bg-gradient-to-b from-{{ $clusterColor }}-50/30 to-white overflow-hidden">

            {{-- Keyword table header (desktop) --}}
            <div class="hidden md:grid grid-cols-12 gap-2 px-5 py-2.5 bg-{{ $clusterColor }}-50/60 border-b border-{{ $clusterColor }}-100/60">
                <div class="col-span-4 text-[10px] font-semibold uppercase tracking-wider text-{{ $clusterColor }}-700">Keyword</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-{{ $clusterColor }}-700 text-right">SV</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-{{ $clusterColor }}-700 text-right">KD</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-{{ $clusterColor }}-700 text-right">CPC</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-{{ $clusterColor }}-700 text-right">Pos</div>
                <div class="col-span-2 text-[10px] font-semibold uppercase tracking-wider text-{{ $clusterColor }}-700 text-center">Intent</div>
                <div class="col-span-2 text-[10px] font-semibold uppercase tracking-wider text-{{ $clusterColor }}-700 text-center">Status</div>
            </div>

            {{-- Keyword rows --}}
            <div class="divide-y divide-[var(--ui-border)]/20">
                @foreach($keywords as $kw)
                    @php
                        $kwKd = $kw->keyword_difficulty;
                        if ($kwKd !== null) {
                            if ($kwKd <= 14) { $kwKdLabel = 'Sehr leicht'; $kwKdColor = 'emerald'; }
                            elseif ($kwKd <= 29) { $kwKdLabel = 'Leicht'; $kwKdColor = 'green'; }
                            elseif ($kwKd <= 49) { $kwKdLabel = 'Machbar'; $kwKdColor = 'yellow'; }
                            elseif ($kwKd <= 69) { $kwKdLabel = 'Schwer'; $kwKdColor = 'orange'; }
                            elseif ($kwKd <= 84) { $kwKdLabel = 'Sehr schwer'; $kwKdColor = 'red'; }
                            else { $kwKdLabel = 'Extrem'; $kwKdColor = 'rose'; }
                        } else {
                            $kwKdLabel = null; $kwKdColor = 'gray';
                        }

                        $kwPos = $kw->position;
                        $kwPosColor = $kwPos !== null
                            ? ($kwPos <= 3 ? 'emerald' : ($kwPos <= 10 ? 'lime' : ($kwPos <= 20 ? 'yellow' : ($kwPos <= 50 ? 'orange' : 'red'))))
                            : 'gray';

                        $intentConfig = [
                            'informational' => ['label' => 'Info', 'color' => 'blue'],
                            'navigational' => ['label' => 'Navi', 'color' => 'purple'],
                            'commercial' => ['label' => 'Kommerz.', 'color' => 'amber'],
                            'transactional' => ['label' => 'Transakt.', 'color' => 'green'],
                        ];
                        $kwIntent = $intentConfig[$kw->search_intent] ?? null;

                        $statusConfig = [
                            'none' => ['label' => 'Offen', 'color' => 'gray'],
                            'planned' => ['label' => 'Geplant', 'color' => 'blue'],
                            'in_progress' => ['label' => 'In Arbeit', 'color' => 'amber'],
                            'published' => ['label' => 'Live', 'color' => 'green'],
                        ];
                        $kwStatus = $statusConfig[$kw->content_status] ?? $statusConfig['none'];
                    @endphp

                    {{-- Desktop row --}}
                    <div class="hidden md:grid grid-cols-12 gap-2 items-center px-5 py-2 hover:bg-white/80 transition-colors">
                        <div class="col-span-4 text-xs font-medium text-[var(--ui-secondary)] truncate">{{ $kw->keyword }}</div>
                        <div class="col-span-1 text-xs tabular-nums text-[var(--ui-secondary)] text-right">
                            {{ $kw->search_volume !== null ? number_format($kw->search_volume) : "\u{2013}" }}
                        </div>
                        <div class="col-span-1 flex justify-end">
                            @if($kwKd !== null)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-{{ $kwKdColor }}-50 text-{{ $kwKdColor }}-700 border border-{{ $kwKdColor }}-200 tabular-nums" title="{{ $kwKdLabel }}">
                                    {{ $kwKd }}
                                </span>
                            @else
                                <span class="text-xs text-[var(--ui-muted)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                        <div class="col-span-1 text-xs tabular-nums text-[var(--ui-secondary)] text-right">
                            {{ $kw->cpc_cents !== null ? number_format($kw->cpc_cents / 100, 2) . ' ' . "\u{20AC}" : "\u{2013}" }}
                        </div>
                        <div class="col-span-1 flex justify-end">
                            @if($kwPos !== null)
                                <span class="inline-flex items-center justify-center min-w-[24px] h-5 px-1 rounded text-[10px] font-bold bg-{{ $kwPosColor }}-50 text-{{ $kwPosColor }}-700 border border-{{ $kwPosColor }}-200 tabular-nums">{{ $kwPos }}</span>
                            @else
                                <span class="text-xs text-[var(--ui-muted)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                        <div class="col-span-2 flex justify-center">
                            @if($kwIntent)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-{{ $kwIntent['color'] }}-50 text-{{ $kwIntent['color'] }}-700 border border-{{ $kwIntent['color'] }}-200">{{ $kwIntent['label'] }}</span>
                            @else
                                <span class="text-xs text-[var(--ui-muted)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                        <div class="col-span-2 flex justify-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-{{ $kwStatus['color'] }}-50 text-{{ $kwStatus['color'] }}-700 border border-{{ $kwStatus['color'] }}-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-{{ $kwStatus['color'] }}-500"></span>
                                {{ $kwStatus['label'] }}
                            </span>
                        </div>
                    </div>

                    {{-- Mobile card --}}
                    <div class="md:hidden px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-xs font-semibold text-[var(--ui-secondary)]">{{ $kw->keyword }}</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-{{ $kwStatus['color'] }}-50 text-{{ $kwStatus['color'] }}-700 border border-{{ $kwStatus['color'] }}-200 flex-shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-{{ $kwStatus['color'] }}-500"></span>
                                {{ $kwStatus['label'] }}
                            </span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            @if($kw->search_volume !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-50 border border-gray-200 text-[10px]">
                                    <span class="text-[var(--ui-muted)]">SV</span>
                                    <span class="font-bold text-[var(--ui-secondary)] tabular-nums">{{ number_format($kw->search_volume) }}</span>
                                </span>
                            @endif
                            @if($kwKd !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-{{ $kwKdColor }}-50 text-{{ $kwKdColor }}-700 border border-{{ $kwKdColor }}-200 tabular-nums">
                                    KD {{ $kwKd }}
                                </span>
                            @endif
                            @if($kw->cpc_cents !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-50 border border-gray-200 text-[10px]">
                                    <span class="text-[var(--ui-muted)]">CPC</span>
                                    <span class="font-bold text-[var(--ui-secondary)] tabular-nums">{{ number_format($kw->cpc_cents / 100, 2) }} {{ "\u{20AC}" }}</span>
                                </span>
                            @endif
                            @if($kwPos !== null)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-{{ $kwPosColor }}-50 text-{{ $kwPosColor }}-700 border border-{{ $kwPosColor }}-200 tabular-nums">
                                    #{{ $kwPos }}
                                </span>
                            @endif
                            @if($kwIntent)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-{{ $kwIntent['color'] }}-50 text-{{ $kwIntent['color'] }}-700 border border-{{ $kwIntent['color'] }}-200">
                                    {{ $kwIntent['label'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Footer summary --}}
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-2.5 bg-{{ $clusterColor }}-50/40 border-t border-{{ $clusterColor }}-100/60">
                <span class="text-[10px] font-medium text-{{ $clusterColor }}-700">{{ $data['count'] }} Keywords</span>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] text-{{ $clusterColor }}-600">
                    <span>SV: <strong class="text-{{ $clusterColor }}-700">{{ number_format($data['sum_sv']) }}</strong></span>
                    <span>Wert: <strong class="text-{{ $clusterColor }}-700">{{ number_format($data['traffic_value'], 0) }} {{ "\u{20AC}" }}</strong></span>
                    <span>Coverage: <strong class="text-{{ $clusterColor }}-700">{{ $data['coverage'] }}%</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>
