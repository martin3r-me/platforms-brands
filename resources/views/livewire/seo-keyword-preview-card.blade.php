@props(['keyword'])

<x-ui-kanban-card :title="''">
    {{-- Keyword --}}
    <div class="mb-2">
        <h4 class="text-sm font-medium text-[var(--ui-secondary)] m-0">
            {{ $keyword->keyword }}
        </h4>
    </div>

    {{-- Metriken --}}
    <div class="flex flex-wrap gap-1.5 mb-2">
        @if($keyword->search_volume !== null)
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-medium">
                @svg('heroicon-o-chart-bar', 'w-2.5 h-2.5')
                {{ number_format($keyword->search_volume) }}
            </span>
        @endif

        @if($keyword->keyword_difficulty !== null)
            @php
                $kdColor = $keyword->keyword_difficulty >= 70 ? 'red' : ($keyword->keyword_difficulty >= 40 ? 'yellow' : 'green');
            @endphp
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-{{ $kdColor }}-50 text-{{ $kdColor }}-700 text-[10px] font-medium">
                KD {{ $keyword->keyword_difficulty }}
            </span>
        @endif

        @if($keyword->cpc_cents !== null)
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-medium">
                {{ number_format($keyword->cpc_cents / 100, 2) }}€
            </span>
        @endif
    </div>

    {{-- Badges --}}
    <div class="flex flex-wrap gap-1 mb-1.5">
        @if($keyword->search_intent)
            @php
                $intentColors = [
                    'informational' => 'blue',
                    'navigational' => 'purple',
                    'commercial' => 'amber',
                    'transactional' => 'green',
                ];
                $intentColor = $intentColors[$keyword->search_intent] ?? 'gray';
            @endphp
            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-{{ $intentColor }}-50 text-{{ $intentColor }}-700">
                {{ ucfirst($keyword->search_intent) }}
            </span>
        @endif

        @if($keyword->priority)
            @php
                $priorityColors = [
                    'high' => 'red',
                    'medium' => 'amber',
                    'low' => 'gray',
                ];
                $priorityColor = $priorityColors[$keyword->priority] ?? 'gray';
            @endphp
            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-{{ $priorityColor }}-50 text-{{ $priorityColor }}-700">
                {{ ucfirst($keyword->priority) }}
            </span>
        @endif

        @if($keyword->trend)
            @php
                $trendIcons = [
                    'up' => 'heroicon-o-arrow-trending-up',
                    'down' => 'heroicon-o-arrow-trending-down',
                    'stable' => 'heroicon-o-minus',
                    'seasonal' => 'heroicon-o-sun',
                ];
                $trendIcon = $trendIcons[$keyword->trend] ?? 'heroicon-o-minus';
                $trendColor = $keyword->trend === 'up' ? 'green' : ($keyword->trend === 'down' ? 'red' : 'gray');
            @endphp
            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-{{ $trendColor }}-50 text-{{ $trendColor }}-700">
                @svg($trendIcon, 'w-2.5 h-2.5')
            </span>
        @endif
    </div>

    {{-- Content Idea --}}
    @if($keyword->content_idea)
        <div class="text-[10px] text-[var(--ui-muted)] mt-1.5 line-clamp-2 italic">
            {{ Str::limit($keyword->content_idea, 80) }}
        </div>
    @endif

    {{-- Position --}}
    @if($keyword->position !== null)
        <div class="mt-1.5">
            <span class="inline-flex items-center gap-1 text-[10px] text-[var(--ui-muted)]">
                @svg('heroicon-o-hashtag', 'w-2.5 h-2.5')
                Position {{ $keyword->position }}
            </span>
        </div>
    @endif
</x-ui-kanban-card>
