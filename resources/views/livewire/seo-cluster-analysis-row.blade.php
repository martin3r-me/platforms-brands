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
@endphp

<tr x-data="{ expanded: false }" class="group">
    {{-- Cluster Name --}}
    <x-ui-table-cell compact="true">
        <button @click="expanded = !expanded" class="flex items-center gap-2 text-left w-full group/btn">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded transition-transform"
                  :class="expanded ? 'rotate-90' : ''">
                @svg('heroicon-o-chevron-right', 'w-3.5 h-3.5 text-[var(--ui-muted)]')
            </span>
            <span class="w-2.5 h-2.5 rounded-full bg-{{ $clusterColor }}-500 flex-shrink-0"></span>
            <span class="text-sm font-medium text-[var(--ui-secondary)] group-hover/btn:text-[var(--ui-primary)] transition-colors">
                {{ $cluster->name }}
            </span>
            <span class="text-[10px] text-[var(--ui-muted)] tabular-nums">{{ $data['count'] }}</span>
        </button>
    </x-ui-table-cell>

    {{-- Opportunity Score --}}
    <x-ui-table-cell compact="true" align="center">
        <div class="flex items-center justify-center gap-2">
            <div class="w-16 h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-{{ $scoreColor }}-500 transition-all" style="width: {{ $score }}%"></div>
            </div>
            <span class="text-xs font-bold text-{{ $scoreColor }}-700 tabular-nums min-w-[28px] text-right">{{ $score }}</span>
        </div>
    </x-ui-table-cell>

    {{-- Σ SV --}}
    <x-ui-table-cell compact="true" align="right">
        <span class="text-xs font-semibold text-[var(--ui-secondary)] tabular-nums">{{ number_format($data['sum_sv']) }}</span>
    </x-ui-table-cell>

    {{-- Ø KD --}}
    <x-ui-table-cell compact="true" align="right">
        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-{{ $kdColor }}-50 text-{{ $kdColor }}-700 border border-{{ $kdColor }}-200 tabular-nums">
            {{ $data['weighted_kd'] }}
        </span>
    </x-ui-table-cell>

    {{-- Ø CPC --}}
    <x-ui-table-cell compact="true" align="right">
        <span class="text-xs text-[var(--ui-secondary)] tabular-nums">{{ number_format($data['avg_cpc'], 2) }}&thinsp;&euro;</span>
    </x-ui-table-cell>

    {{-- Traffic-Wert --}}
    <x-ui-table-cell compact="true" align="right">
        <span class="text-xs font-semibold text-emerald-700 tabular-nums">{{ number_format($data['traffic_value'], 0) }}&thinsp;&euro;</span>
    </x-ui-table-cell>

    {{-- Coverage --}}
    <x-ui-table-cell compact="true" align="right">
        <div class="flex items-center justify-end gap-1.5">
            <div class="w-10 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-lime-500 transition-all" style="width: {{ $data['coverage'] }}%"></div>
            </div>
            <span class="text-xs text-[var(--ui-muted)] tabular-nums">{{ $data['coverage'] }}%</span>
        </div>
    </x-ui-table-cell>

    {{-- Rankings --}}
    <x-ui-table-cell compact="true" align="right">
        <span class="text-xs text-[var(--ui-secondary)] tabular-nums">{{ $data['rankings'] }}/{{ $data['count'] }}</span>
    </x-ui-table-cell>

    {{-- Ø Position --}}
    <x-ui-table-cell compact="true" align="right">
        @if($data['avg_position'] !== null)
            @php
                $pos = $data['avg_position'];
                $posColor = $pos <= 3 ? 'emerald' : ($pos <= 10 ? 'lime' : ($pos <= 20 ? 'yellow' : ($pos <= 50 ? 'orange' : 'red')));
            @endphp
            <span class="inline-flex items-center justify-center min-w-[28px] h-5 px-1 rounded text-[11px] font-bold bg-{{ $posColor }}-50 text-{{ $posColor }}-700 border border-{{ $posColor }}-200 tabular-nums">
                {{ $data['avg_position'] }}
            </span>
        @else
            <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
        @endif
    </x-ui-table-cell>
</tr>

{{-- Expanded: Keyword Sub-Table --}}
<tr x-show="expanded" x-collapse.duration.200ms>
    <td colspan="9" class="p-0">
        <div class="bg-[var(--ui-muted-5)] border-y border-[var(--ui-border)]/30">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-{{ $clusterColor }}-50/50">
                        <th class="px-4 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-{{ $clusterColor }}-700">Keyword</th>
                        <th class="px-3 py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-{{ $clusterColor }}-700">SV</th>
                        <th class="px-3 py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-{{ $clusterColor }}-700">KD</th>
                        <th class="px-3 py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-{{ $clusterColor }}-700">CPC</th>
                        <th class="px-3 py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-{{ $clusterColor }}-700">Pos</th>
                        <th class="px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wide text-{{ $clusterColor }}-700">Intent</th>
                        <th class="px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wide text-{{ $clusterColor }}-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--ui-border)]/20">
                    @foreach($keywords as $kw)
                        @php
                            // KD color per keyword
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

                            // Position color
                            $kwPos = $kw->position;
                            $kwPosColor = $kwPos !== null
                                ? ($kwPos <= 3 ? 'emerald' : ($kwPos <= 10 ? 'lime' : ($kwPos <= 20 ? 'yellow' : ($kwPos <= 50 ? 'orange' : 'red'))))
                                : 'gray';

                            // Intent
                            $intentConfig = [
                                'informational' => ['label' => 'Info', 'color' => 'blue'],
                                'navigational' => ['label' => 'Navi', 'color' => 'purple'],
                                'commercial' => ['label' => 'Kommerz.', 'color' => 'amber'],
                                'transactional' => ['label' => 'Transakt.', 'color' => 'green'],
                            ];
                            $kwIntent = $intentConfig[$kw->search_intent] ?? null;

                            // Status
                            $statusConfig = [
                                'none' => ['label' => 'Offen', 'color' => 'gray'],
                                'planned' => ['label' => 'Geplant', 'color' => 'blue'],
                                'in_progress' => ['label' => 'In Arbeit', 'color' => 'amber'],
                                'published' => ['label' => 'Live', 'color' => 'green'],
                            ];
                            $kwStatus = $statusConfig[$kw->content_status] ?? $statusConfig['none'];
                        @endphp
                        <tr class="hover:bg-white/60 transition-colors">
                            {{-- Keyword --}}
                            <td class="px-4 py-1.5">
                                <span class="text-xs font-medium text-[var(--ui-secondary)]">{{ $kw->keyword }}</span>
                            </td>
                            {{-- SV --}}
                            <td class="px-3 py-1.5 text-right">
                                <span class="text-xs tabular-nums text-[var(--ui-secondary)]">{{ $kw->search_volume !== null ? number_format($kw->search_volume) : '–' }}</span>
                            </td>
                            {{-- KD --}}
                            <td class="px-3 py-1.5 text-right">
                                @if($kwKd !== null)
                                    <span class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded text-[10px] font-bold bg-{{ $kwKdColor }}-50 text-{{ $kwKdColor }}-700 border border-{{ $kwKdColor }}-200 tabular-nums">
                                        {{ $kwKd }}
                                        <span class="text-[8px] font-medium">{{ $kwKdLabel }}</span>
                                    </span>
                                @else
                                    <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
                                @endif
                            </td>
                            {{-- CPC --}}
                            <td class="px-3 py-1.5 text-right">
                                <span class="text-xs tabular-nums text-[var(--ui-secondary)]">{{ $kw->cpc_cents !== null ? number_format($kw->cpc_cents / 100, 2) . ' €' : '–' }}</span>
                            </td>
                            {{-- Position --}}
                            <td class="px-3 py-1.5 text-right">
                                @if($kwPos !== null)
                                    <span class="inline-flex items-center justify-center min-w-[22px] h-4 px-1 rounded text-[10px] font-bold bg-{{ $kwPosColor }}-50 text-{{ $kwPosColor }}-700 border border-{{ $kwPosColor }}-200 tabular-nums">
                                        {{ $kwPos }}
                                    </span>
                                @else
                                    <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
                                @endif
                            </td>
                            {{-- Intent --}}
                            <td class="px-3 py-1.5 text-center">
                                @if($kwIntent)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-{{ $kwIntent['color'] }}-50 text-{{ $kwIntent['color'] }}-700 border border-{{ $kwIntent['color'] }}-200">
                                        {{ $kwIntent['label'] }}
                                    </span>
                                @else
                                    <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
                                @endif
                            </td>
                            {{-- Status --}}
                            <td class="px-3 py-1.5 text-center">
                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-{{ $kwStatus['color'] }}-50 text-{{ $kwStatus['color'] }}-700 border border-{{ $kwStatus['color'] }}-200">
                                    <span class="w-1 h-1 rounded-full bg-{{ $kwStatus['color'] }}-500"></span>
                                    {{ $kwStatus['label'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </td>
</tr>
