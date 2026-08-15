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

    // Map internal color-scale to nx-badge variants (quiet, tokenized).
    $nxVariant = fn (?string $c) => match ($c) {
        'emerald', 'green', 'lime' => 'success',
        'yellow', 'orange', 'amber' => 'warning',
        'red', 'rose' => 'danger',
        'blue' => 'info',
        'purple' => 'accent',
        default => 'neutral',
    };
@endphp

<div x-data="{ expanded: false }" class="group">
    {{-- Cluster Row (ruhiger Hairline-Block) --}}
    <div @click="expanded = !expanded"
         class="cursor-pointer rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] transition-colors hover:bg-[color:var(--nx-hover)]"
         :class="expanded ? 'border-[color:var(--nx-line-strong)]' : ''">

        <div class="px-4 py-3">
            {{-- Desktop: single row --}}
            <div class="hidden lg:flex items-center gap-4">
                {{-- Expand icon --}}
                <div class="flex-shrink-0 flex items-center justify-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-md transition-transform duration-200"
                          :class="expanded ? 'rotate-90' : ''">
                        @svg('heroicon-o-chevron-right', 'w-3.5 h-3.5 text-[color:var(--nx-faint)]')
                    </span>
                </div>

                {{-- Cluster name + value badge --}}
                <div class="flex-1 min-w-0 flex items-center gap-2.5">
                    <span class="text-sm font-semibold text-[color:var(--nx-text)] truncate">{{ $cluster->name }}</span>
                    <x-nx-badge class="flex-shrink-0 tabular-nums">{{ $data['count'] }} KW</x-nx-badge>
                    @if($data['traffic_value'] > 0)
                        <x-nx-badge variant="success" class="flex-shrink-0 tabular-nums">{{ number_format($data['traffic_value'], 0) }} {{ "\u{20AC}" }}</x-nx-badge>
                    @endif
                </div>

                {{-- Metrics row --}}
                <div class="flex items-center gap-5 flex-shrink-0">
                    {{-- Score --}}
                    <div class="flex items-center gap-2 w-32" title="Opportunity Score: Wert/KD {{ "\u{00D7}" }} Coverage-L{{ "\u{00FC}" }}cke {{ "\u{00D7}" }} Pos-Boost ({{ $data['position_boost'] ?? 1.0 }}x)">
                        <div class="flex-1 h-1.5 bg-[color:var(--nx-hover)] rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-[color:var(--nx-accent)] transition-all" style="width: {{ $score }}%"></div>
                        </div>
                        <span class="text-xs font-bold text-[color:var(--nx-text)] tabular-nums w-7 text-right">{{ $score }}</span>
                        @if(($data['position_boost'] ?? 1.0) >= 1.5)
                            <span class="text-[color:var(--nx-warning)] flex-shrink-0" title="Low-hanging fruit: Pos 11-20">@svg('heroicon-s-bolt', 'w-3.5 h-3.5')</span>
                        @endif
                    </div>

                    {{-- SV --}}
                    <div class="w-16 text-right">
                        <span class="text-xs font-semibold text-[color:var(--nx-text)] tabular-nums">{{ number_format($data['sum_sv']) }}</span>
                    </div>

                    {{-- KD --}}
                    <div class="w-12 flex justify-end">
                        <x-nx-badge :variant="$nxVariant($kdColor)" class="tabular-nums">{{ $data['weighted_kd'] }}</x-nx-badge>
                    </div>

                    {{-- Traffic-Wert --}}
                    <div class="w-16 text-right">
                        <span class="text-xs font-semibold text-[color:var(--nx-success)] tabular-nums">{{ number_format($data['traffic_value'], 0) }} {{ "\u{20AC}" }}</span>
                    </div>

                    {{-- Coverage --}}
                    <div class="w-24 flex items-center justify-end gap-1.5">
                        <div class="w-12 h-1.5 bg-[color:var(--nx-hover)] rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-[color:var(--nx-accent)] transition-all" style="width: {{ $data['coverage'] }}%"></div>
                        </div>
                        <span class="text-[11px] text-[color:var(--nx-faint)] tabular-nums w-8 text-right">{{ $data['coverage'] }}%</span>
                    </div>

                    {{-- Position --}}
                    <div class="w-12 flex justify-end">
                        @if($pos !== null)
                            <x-nx-badge :variant="$nxVariant($posColor)" class="tabular-nums">{{ $data['avg_position'] }}</x-nx-badge>
                        @else
                            <span class="text-xs text-[color:var(--nx-faint)]">{{ "\u{2013}" }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Mobile/Tablet: stacked layout --}}
            <div class="lg:hidden">
                {{-- Header row --}}
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-md transition-transform duration-200"
                          :class="expanded ? 'rotate-90' : ''">
                        @svg('heroicon-o-chevron-right', 'w-3.5 h-3.5 text-[color:var(--nx-faint)]')
                    </span>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-semibold text-[color:var(--nx-text)] truncate block">{{ $cluster->name }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        @if($data['traffic_value'] > 0)
                            <x-nx-badge variant="success" class="tabular-nums">{{ number_format($data['traffic_value'], 0) }} {{ "\u{20AC}" }}</x-nx-badge>
                        @endif
                        <x-nx-badge class="tabular-nums">{{ $data['count'] }}</x-nx-badge>
                    </div>
                </div>

                {{-- Metrics grid --}}
                <div class="mt-2.5 ml-9 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2">
                    <div>
                        <div class="text-[10px] text-[color:var(--nx-faint)] uppercase tracking-wide">Score</div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <div class="w-10 h-1.5 bg-[color:var(--nx-hover)] rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-[color:var(--nx-accent)]" style="width: {{ $score }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-[color:var(--nx-text)] tabular-nums">{{ $score }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[color:var(--nx-faint)] uppercase tracking-wide">SV</div>
                        <div class="text-xs font-semibold text-[color:var(--nx-text)] tabular-nums mt-0.5">{{ number_format($data['sum_sv']) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[color:var(--nx-faint)] uppercase tracking-wide">KD</div>
                        <div class="mt-0.5">
                            <x-nx-badge :variant="$nxVariant($kdColor)" class="tabular-nums">{{ $data['weighted_kd'] }}</x-nx-badge>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[color:var(--nx-faint)] uppercase tracking-wide">Wert</div>
                        <div class="text-xs font-semibold text-[color:var(--nx-success)] tabular-nums mt-0.5">{{ number_format($data['traffic_value'], 0) }} {{ "\u{20AC}" }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Expanded: Keyword list --}}
    <div x-show="expanded" x-collapse.duration.200ms>
        <div class="mt-1 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] overflow-hidden">

            {{-- Keyword table header (desktop) --}}
            <div class="hidden md:grid grid-cols-12 gap-2 px-5 py-2.5 bg-[color:var(--nx-hover)] border-b border-[color:var(--nx-line)]">
                <div class="col-span-4 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)]">Keyword</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-right">SV</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-right">KD</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-right">CPC</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-right">Pos</div>
                <div class="col-span-2 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-center">Intent</div>
                <div class="col-span-2 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-center">Status</div>
            </div>

            {{-- Keyword rows --}}
            <div class="divide-y divide-[color:var(--nx-line)]">
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
                    <div class="hidden md:grid grid-cols-12 gap-2 items-center px-5 py-2 hover:bg-[color:var(--nx-hover)] transition-colors">
                        <div class="col-span-4 text-xs font-medium text-[color:var(--nx-text)] truncate">{{ $kw->keyword }}</div>
                        <div class="col-span-1 text-xs tabular-nums text-[color:var(--nx-text)] text-right">
                            {{ $kw->search_volume !== null ? number_format($kw->search_volume) : "\u{2013}" }}
                        </div>
                        <div class="col-span-1 flex justify-end">
                            @if($kwKd !== null)
                                <x-nx-badge :variant="$nxVariant($kwKdColor)" class="tabular-nums" :title="$kwKdLabel">{{ $kwKd }}</x-nx-badge>
                            @else
                                <span class="text-xs text-[color:var(--nx-faint)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                        <div class="col-span-1 text-xs tabular-nums text-[color:var(--nx-text)] text-right">
                            {{ $kw->cpc_cents !== null ? number_format($kw->cpc_cents / 100, 2) . ' ' . "\u{20AC}" : "\u{2013}" }}
                        </div>
                        <div class="col-span-1 flex justify-end">
                            @if($kwPos !== null)
                                <x-nx-badge :variant="$nxVariant($kwPosColor)" class="tabular-nums">{{ $kwPos }}</x-nx-badge>
                            @else
                                <span class="text-xs text-[color:var(--nx-faint)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                        <div class="col-span-2 flex justify-center">
                            @if($kwIntent)
                                <x-nx-badge :variant="$nxVariant($kwIntent['color'])">{{ $kwIntent['label'] }}</x-nx-badge>
                            @else
                                <span class="text-xs text-[color:var(--nx-faint)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                        <div class="col-span-2 flex justify-center">
                            <x-nx-badge :variant="$nxVariant($kwStatus['color'])" dot>{{ $kwStatus['label'] }}</x-nx-badge>
                        </div>
                    </div>

                    {{-- Mobile card --}}
                    <div class="md:hidden px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-xs font-semibold text-[color:var(--nx-text)]">{{ $kw->keyword }}</span>
                            <x-nx-badge :variant="$nxVariant($kwStatus['color'])" dot class="flex-shrink-0">{{ $kwStatus['label'] }}</x-nx-badge>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            @if($kw->search_volume !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border border-[color:var(--nx-line)]">
                                    <span class="text-[color:var(--nx-faint)]">SV</span>
                                    <span class="font-bold text-[color:var(--nx-text)] tabular-nums">{{ number_format($kw->search_volume) }}</span>
                                </span>
                            @endif
                            @if($kwKd !== null)
                                <x-nx-badge :variant="$nxVariant($kwKdColor)" class="tabular-nums">KD {{ $kwKd }}</x-nx-badge>
                            @endif
                            @if($kw->cpc_cents !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border border-[color:var(--nx-line)]">
                                    <span class="text-[color:var(--nx-faint)]">CPC</span>
                                    <span class="font-bold text-[color:var(--nx-text)] tabular-nums">{{ number_format($kw->cpc_cents / 100, 2) }} {{ "\u{20AC}" }}</span>
                                </span>
                            @endif
                            @if($kwPos !== null)
                                <x-nx-badge :variant="$nxVariant($kwPosColor)" class="tabular-nums">#{{ $kwPos }}</x-nx-badge>
                            @endif
                            @if($kwIntent)
                                <x-nx-badge :variant="$nxVariant($kwIntent['color'])">{{ $kwIntent['label'] }}</x-nx-badge>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Footer summary --}}
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-2.5 bg-[color:var(--nx-hover)] border-t border-[color:var(--nx-line)]">
                <span class="text-[10px] font-medium text-[color:var(--nx-faint)]">{{ $data['count'] }} Keywords</span>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] text-[color:var(--nx-faint)]">
                    <span>SV: <strong class="text-[color:var(--nx-text)]">{{ number_format($data['sum_sv']) }}</strong></span>
                    <span>Wert: <strong class="text-[color:var(--nx-text)]">{{ number_format($data['traffic_value'], 0) }} {{ "\u{20AC}" }}</strong></span>
                    <span>Coverage: <strong class="text-[color:var(--nx-text)]">{{ $data['coverage'] }}%</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>
