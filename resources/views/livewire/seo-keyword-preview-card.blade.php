@props(['keyword', 'maxSearchVolume' => 1])

@php
    // Search Volume Bar
    $svPercent = ($maxSearchVolume > 0 && $keyword->search_volume) ? round(($keyword->search_volume / $maxSearchVolume) * 100) : 0;

    // KD Label + nx-Badge-Variant
    $kd = $keyword->keyword_difficulty;
    if ($kd !== null) {
        if ($kd <= 14) { $kdLabel = 'Sehr leicht'; $kdVariant = 'success'; }
        elseif ($kd <= 29) { $kdLabel = 'Leicht'; $kdVariant = 'success'; }
        elseif ($kd <= 49) { $kdLabel = 'Machbar'; $kdVariant = 'warning'; }
        elseif ($kd <= 69) { $kdLabel = 'Schwer'; $kdVariant = 'warning'; }
        elseif ($kd <= 84) { $kdLabel = 'Sehr schwer'; $kdVariant = 'danger'; }
        else { $kdLabel = 'Extrem'; $kdVariant = 'danger'; }
    } else {
        $kdLabel = null; $kdVariant = 'neutral';
    }

    // Intent
    $intentConfig = [
        'informational' => ['label' => 'Info', 'icon' => 'heroicon-o-information-circle', 'variant' => 'info'],
        'navigational' => ['label' => 'Navi', 'icon' => 'heroicon-o-map-pin', 'variant' => 'accent'],
        'commercial' => ['label' => 'Kommerziell', 'icon' => 'heroicon-o-shopping-bag', 'variant' => 'warning'],
        'transactional' => ['label' => 'Transaktional', 'icon' => 'heroicon-o-banknotes', 'variant' => 'success'],
    ];
    $intent = $intentConfig[$keyword->search_intent] ?? null;

    // Priority
    $priorityConfig = [
        'high' => ['label' => 'Hoch', 'variant' => 'danger', 'icon' => 'heroicon-s-chevron-double-up'],
        'medium' => ['label' => 'Mittel', 'variant' => 'warning', 'icon' => 'heroicon-o-minus'],
        'low' => ['label' => 'Niedrig', 'variant' => 'neutral', 'icon' => 'heroicon-s-chevron-double-down'],
    ];
    $prio = $priorityConfig[$keyword->priority] ?? null;

    // Trend
    $trendConfig = [
        'up' => ['icon' => 'heroicon-o-arrow-trending-up', 'variant' => 'success'],
        'down' => ['icon' => 'heroicon-o-arrow-trending-down', 'variant' => 'danger'],
        'stable' => ['icon' => 'heroicon-o-minus', 'variant' => 'neutral'],
        'seasonal' => ['icon' => 'heroicon-o-sun', 'variant' => 'warning'],
    ];
    $trend = $trendConfig[$keyword->trend] ?? null;

    // Content Status
    $statusConfig = [
        'none' => ['label' => 'Offen', 'variant' => 'neutral'],
        'planned' => ['label' => 'Geplant', 'variant' => 'info'],
        'in_progress' => ['label' => 'In Arbeit', 'variant' => 'warning'],
        'published' => ['label' => 'Live', 'variant' => 'success'],
    ];
    $status = $statusConfig[$keyword->content_status] ?? $statusConfig['none'];

    // Position color -> nx-Badge-Variant
    $pos = $keyword->position;
    $posVariant = $pos !== null
        ? ($pos <= 3 ? 'success' : ($pos <= 10 ? 'success' : ($pos <= 20 ? 'warning' : ($pos <= 50 ? 'warning' : 'danger'))))
        : 'neutral';
@endphp

<x-nx-kanban-card :title="''" class="seo-keyword-card">
    {{-- Keyword Title --}}
    <div class="mb-2">
        <h4 class="m-0 text-sm font-semibold leading-tight text-[color:var(--nx-text)]">
            @svg('heroicon-o-magnifying-glass', 'w-3 h-3 inline-block text-[color:var(--nx-accent)] mr-0.5')
            {{ $keyword->keyword }}
        </h4>
    </div>

    {{-- Metrics --}}
    <div class="mb-2 space-y-1.5">
        {{-- Suchvolumen --}}
        @if($keyword->search_volume !== null)
            <div class="flex items-center justify-between gap-2">
                <span class="w-7 flex-shrink-0 text-[10px] uppercase tracking-wide text-[color:var(--nx-faint)]">SV</span>
                <div class="h-1.5 flex-grow overflow-hidden rounded-full bg-[color:var(--nx-hover)]">
                    <div class="h-full rounded-full bg-[color:var(--nx-accent)] transition-all" style="width: {{ $svPercent }}%"></div>
                </div>
                <span class="min-w-[36px] text-right text-[11px] font-bold tabular-nums text-[color:var(--nx-text)]">
                    {{ number_format($keyword->search_volume) }}
                </span>
            </div>
        @endif

        {{-- Keyword Difficulty --}}
        @if($kd !== null)
            <div class="flex items-center justify-between gap-2">
                <span class="w-7 flex-shrink-0 text-[10px] uppercase tracking-wide text-[color:var(--nx-faint)]">KD</span>
                <div class="h-1.5 flex-grow overflow-hidden rounded-full bg-[color:var(--nx-hover)]">
                    <div class="h-full rounded-full bg-[color:var(--nx-muted)] transition-all" style="width: {{ $kd }}%"></div>
                </div>
                <span class="text-[10px] font-bold tabular-nums text-[color:var(--nx-text)]">{{ $kd }}</span>
            </div>
            <div class="flex justify-end -mt-0.5">
                <x-nx-badge :variant="$kdVariant">{{ $kdLabel }}</x-nx-badge>
            </div>
        @endif

        {{-- CPC --}}
        @if($keyword->cpc_cents !== null)
            <div class="flex items-center justify-between gap-2">
                <span class="w-7 flex-shrink-0 text-[10px] uppercase tracking-wide text-[color:var(--nx-faint)]">CPC</span>
                <x-nx-badge variant="success">
                    <span class="tabular-nums">{{ number_format($keyword->cpc_cents / 100, 2) }}&thinsp;&euro;</span>
                </x-nx-badge>
            </div>
        @endif

        {{-- Position --}}
        @if($pos !== null)
            <div class="flex items-center justify-between gap-2">
                <span class="w-7 flex-shrink-0 text-[10px] uppercase tracking-wide text-[color:var(--nx-faint)]">Pos</span>
                <div class="flex items-center gap-1.5">
                    <x-nx-badge :variant="$posVariant">
                        <span class="tabular-nums">{{ $pos }}</span>
                    </x-nx-badge>
                    @if($keyword->target_position)
                        <span class="text-[9px] text-[color:var(--nx-faint)]">
                            @svg('heroicon-o-arrow-right', 'w-2.5 h-2.5 inline-block')
                            {{ $keyword->target_position }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Badges --}}
    <div class="mb-1.5 flex flex-wrap gap-1">
        @if($intent)
            <x-nx-badge :variant="$intent['variant']">
                @svg($intent['icon'], 'w-2.5 h-2.5')
                {{ $intent['label'] }}
            </x-nx-badge>
        @endif
        @if($prio)
            <x-nx-badge :variant="$prio['variant']">
                @svg($prio['icon'], 'w-2.5 h-2.5')
                {{ $prio['label'] }}
            </x-nx-badge>
        @endif
        @if($trend)
            <x-nx-badge :variant="$trend['variant']">
                @svg($trend['icon'], 'w-2.5 h-2.5')
            </x-nx-badge>
        @endif
        <x-nx-badge :variant="$status['variant']" dot>{{ $status['label'] }}</x-nx-badge>
    </div>

    {{-- Content Idea --}}
    @if($keyword->content_idea)
        <div class="line-clamp-2 text-[10px] italic leading-relaxed text-[color:var(--nx-faint)]">
            {{ Str::limit($keyword->content_idea, 80) }}
        </div>
    @endif

    {{-- URL --}}
    @if($keyword->published_url || $keyword->target_url)
        @php $displayUrl = $keyword->published_url ?? $keyword->target_url; @endphp
        <div class="mt-1 flex items-center gap-1">
            @svg('heroicon-o-link', 'w-2.5 h-2.5 text-[color:var(--nx-faint)] flex-shrink-0')
            <a href="{{ $displayUrl }}" target="_blank" class="max-w-[200px] truncate text-[10px] text-[color:var(--nx-accent)] hover:underline" title="{{ $displayUrl }}">
                {{ Str::limit(str_replace(['https://', 'http://', 'www.'], '', $displayUrl), 35) }}
            </a>
            @if($keyword->published_url)
                <x-nx-badge variant="success">LIVE</x-nx-badge>
            @endif
        </div>
    @endif

    {{-- Last Fetched --}}
    @if($keyword->last_fetched_at)
        <x-slot name="footer">
            <span class="text-[9px] text-[color:var(--nx-faint)]">
                @svg('heroicon-o-clock', 'w-2.5 h-2.5 inline-block')
                {{ $keyword->last_fetched_at->diffForHumans() }}
            </span>
        </x-slot>
    @endif
</x-nx-kanban-card>
