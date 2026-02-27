@props(['keyword', 'maxSearchVolume' => 1])

@php
    // Search Volume Bar
    $svPercent = ($maxSearchVolume > 0 && $keyword->search_volume) ? round(($keyword->search_volume / $maxSearchVolume) * 100) : 0;

    // KD Label + Color
    $kd = $keyword->keyword_difficulty;
    if ($kd !== null) {
        if ($kd <= 14) { $kdLabel = 'Sehr leicht'; $kdColor = 'emerald'; }
        elseif ($kd <= 29) { $kdLabel = 'Leicht'; $kdColor = 'green'; }
        elseif ($kd <= 49) { $kdLabel = 'Machbar'; $kdColor = 'yellow'; }
        elseif ($kd <= 69) { $kdLabel = 'Schwer'; $kdColor = 'orange'; }
        elseif ($kd <= 84) { $kdLabel = 'Sehr schwer'; $kdColor = 'red'; }
        else { $kdLabel = 'Extrem'; $kdColor = 'rose'; }
    } else {
        $kdLabel = null; $kdColor = 'gray';
    }

    // Intent
    $intentConfig = [
        'informational' => ['label' => 'Info', 'icon' => 'heroicon-o-information-circle', 'color' => 'blue'],
        'navigational' => ['label' => 'Navi', 'icon' => 'heroicon-o-map-pin', 'color' => 'purple'],
        'commercial' => ['label' => 'Kommerziell', 'icon' => 'heroicon-o-shopping-bag', 'color' => 'amber'],
        'transactional' => ['label' => 'Transaktional', 'icon' => 'heroicon-o-banknotes', 'color' => 'green'],
    ];
    $intent = $intentConfig[$keyword->search_intent] ?? null;

    // Priority
    $priorityConfig = [
        'high' => ['label' => 'Hoch', 'color' => 'red', 'icon' => 'heroicon-s-chevron-double-up'],
        'medium' => ['label' => 'Mittel', 'color' => 'amber', 'icon' => 'heroicon-o-minus'],
        'low' => ['label' => 'Niedrig', 'color' => 'gray', 'icon' => 'heroicon-s-chevron-double-down'],
    ];
    $prio = $priorityConfig[$keyword->priority] ?? null;

    // Trend
    $trendConfig = [
        'up' => ['icon' => 'heroicon-o-arrow-trending-up', 'color' => 'green'],
        'down' => ['icon' => 'heroicon-o-arrow-trending-down', 'color' => 'red'],
        'stable' => ['icon' => 'heroicon-o-minus', 'color' => 'gray'],
        'seasonal' => ['icon' => 'heroicon-o-sun', 'color' => 'amber'],
    ];
    $trend = $trendConfig[$keyword->trend] ?? null;

    // Content Status
    $statusConfig = [
        'none' => ['label' => 'Offen', 'color' => 'gray'],
        'planned' => ['label' => 'Geplant', 'color' => 'blue'],
        'in_progress' => ['label' => 'In Arbeit', 'color' => 'amber'],
        'published' => ['label' => 'Live', 'color' => 'green'],
    ];
    $status = $statusConfig[$keyword->content_status] ?? $statusConfig['none'];

    // Position color
    $pos = $keyword->position;
    $posColor = $pos !== null
        ? ($pos <= 3 ? 'emerald' : ($pos <= 10 ? 'lime' : ($pos <= 20 ? 'yellow' : ($pos <= 50 ? 'orange' : 'red'))))
        : 'gray';
@endphp

<x-ui-kanban-card :title="''" class="seo-keyword-card">
    {{-- Keyword Title --}}
    <div class="mb-2">
        <h4 class="text-sm font-semibold text-[var(--ui-secondary)] m-0 leading-tight">
            @svg('heroicon-o-magnifying-glass', 'w-3 h-3 inline-block text-lime-500 mr-0.5')
            {{ $keyword->keyword }}
        </h4>
    </div>

    {{-- Metrics --}}
    <div class="space-y-1.5 mb-2">
        {{-- Suchvolumen --}}
        @if($keyword->search_volume !== null)
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide w-7 flex-shrink-0">SV</span>
                <div class="flex-grow h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full bg-lime-500 transition-all" style="width: {{ $svPercent }}%"></div>
                </div>
                <span class="text-[11px] font-bold text-[var(--ui-secondary)] tabular-nums min-w-[36px] text-right">
                    {{ number_format($keyword->search_volume) }}
                </span>
            </div>
        @endif

        {{-- Keyword Difficulty --}}
        @if($kd !== null)
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide w-7 flex-shrink-0">KD</span>
                <div class="flex-grow h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full bg-{{ $kdColor }}-500 transition-all" style="width: {{ $kd }}%"></div>
                </div>
                <span class="text-[10px] font-bold text-{{ $kdColor }}-700 tabular-nums">{{ $kd }}</span>
            </div>
            <div class="flex justify-end -mt-0.5">
                <span class="text-[9px] font-medium text-{{ $kdColor }}-600 bg-{{ $kdColor }}-50 px-1.5 rounded-full border border-{{ $kdColor }}-200">
                    {{ $kdLabel }}
                </span>
            </div>
        @endif

        {{-- CPC --}}
        @if($keyword->cpc_cents !== null)
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide w-7 flex-shrink-0">CPC</span>
                <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 tabular-nums">
                    {{ number_format($keyword->cpc_cents / 100, 2) }}&thinsp;&euro;
                </span>
            </div>
        @endif

        {{-- Position --}}
        @if($pos !== null)
            <div class="flex items-center justify-between gap-2">
                <span class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide w-7 flex-shrink-0">Pos</span>
                <div class="flex items-center gap-1.5">
                    <span class="inline-flex items-center justify-center min-w-[24px] h-5 px-1 rounded text-[11px] font-bold bg-{{ $posColor }}-50 text-{{ $posColor }}-700 border border-{{ $posColor }}-200 tabular-nums">
                        {{ $pos }}
                    </span>
                    @if($keyword->target_position)
                        <span class="text-[9px] text-[var(--ui-muted)]">
                            @svg('heroicon-o-arrow-right', 'w-2.5 h-2.5 inline-block')
                            {{ $keyword->target_position }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Badges --}}
    <div class="flex flex-wrap gap-1 mb-1.5">
        @if($intent)
            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-{{ $intent['color'] }}-50 text-{{ $intent['color'] }}-700 border border-{{ $intent['color'] }}-200">
                @svg($intent['icon'], 'w-2.5 h-2.5')
                {{ $intent['label'] }}
            </span>
        @endif
        @if($prio)
            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-{{ $prio['color'] }}-50 text-{{ $prio['color'] }}-700 border border-{{ $prio['color'] }}-200">
                @svg($prio['icon'], 'w-2.5 h-2.5')
                {{ $prio['label'] }}
            </span>
        @endif
        @if($trend)
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-{{ $trend['color'] }}-50 border border-{{ $trend['color'] }}-200">
                @svg($trend['icon'], 'w-3 h-3 text-' . $trend['color'] . '-600')
            </span>
        @endif
        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-{{ $status['color'] }}-50 text-{{ $status['color'] }}-700 border border-{{ $status['color'] }}-200">
            <span class="w-1.5 h-1.5 rounded-full bg-{{ $status['color'] }}-500"></span>
            {{ $status['label'] }}
        </span>
    </div>

    {{-- Content Idea --}}
    @if($keyword->content_idea)
        <div class="text-[10px] text-[var(--ui-muted)] line-clamp-2 italic leading-relaxed">
            {{ Str::limit($keyword->content_idea, 80) }}
        </div>
    @endif

    {{-- URL --}}
    @if($keyword->published_url || $keyword->target_url)
        @php $displayUrl = $keyword->published_url ?? $keyword->target_url; @endphp
        <div class="flex items-center gap-1 mt-1">
            @svg('heroicon-o-link', 'w-2.5 h-2.5 text-[var(--ui-muted)] flex-shrink-0')
            <a href="{{ $displayUrl }}" target="_blank" class="text-[10px] text-lime-700 hover:text-lime-900 hover:underline truncate max-w-[200px]" title="{{ $displayUrl }}">
                {{ Str::limit(str_replace(['https://', 'http://', 'www.'], '', $displayUrl), 35) }}
            </a>
            @if($keyword->published_url)
                <span class="text-[8px] font-bold text-green-700 bg-green-100 px-1 rounded">LIVE</span>
            @endif
        </div>
    @endif

    {{-- Last Fetched --}}
    @if($keyword->last_fetched_at)
        <x-slot name="footer">
            <span class="text-[9px] text-[var(--ui-muted)]">
                @svg('heroicon-o-clock', 'w-2.5 h-2.5 inline-block')
                {{ $keyword->last_fetched_at->diffForHumans() }}
            </span>
        </x-slot>
    @endif
</x-ui-kanban-card>
