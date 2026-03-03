@php
    $domain = $data['domain'];
    $keywords = $data['keywords'];
    $avgPos = $data['avg_serp_position'];
    $strategicMatch = $strategicCompetitorMap[$domain] ?? null;

    // Position color
    $posColor = $avgPos <= 3 ? 'emerald' : ($avgPos <= 10 ? 'lime' : ($avgPos <= 20 ? 'yellow' : ($avgPos <= 50 ? 'orange' : 'red')));

    // Gap severity color
    $gapCount = $data['gap_count'];
    $gapColor = $gapCount === 0 ? 'emerald' : ($gapCount <= 3 ? 'yellow' : ($gapCount <= 10 ? 'orange' : 'red'));
@endphp

<div x-data="{ expanded: false }" class="group">
    {{-- Domain Card --}}
    <div @click="expanded = !expanded"
         class="relative cursor-pointer rounded-xl border bg-white transition-all duration-200
                hover:shadow-md hover:border-lime-300
                border-[var(--ui-border)]/60"
         :class="expanded ? 'shadow-md border-lime-300 ring-1 ring-lime-100' : ''">

        {{-- Lime accent bar --}}
        <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl bg-lime-500"></div>

        <div class="pl-5 pr-4 py-3">
            {{-- Desktop: single row --}}
            <div class="hidden lg:flex items-center gap-4">
                {{-- Expand icon --}}
                <div class="flex-shrink-0 flex items-center justify-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg transition-all duration-200 group-hover:bg-lime-50"
                          :class="expanded ? 'bg-lime-50 rotate-90' : ''">
                        @svg('heroicon-o-chevron-right', 'w-3.5 h-3.5 text-[var(--ui-muted)] transition-transform')
                    </span>
                </div>

                {{-- Domain name + badges --}}
                <div class="flex-1 min-w-0 flex items-center gap-2.5">
                    @if($strategicMatch && $strategicMatch['logo_url'])
                        <img src="{{ $strategicMatch['logo_url'] }}" alt="" class="w-5 h-5 rounded-full object-cover flex-shrink-0">
                    @else
                        @svg('heroicon-o-globe-alt', 'w-4 h-4 text-lime-500 flex-shrink-0')
                    @endif
                    <span class="text-sm font-semibold text-[var(--ui-secondary)] truncate">{{ $domain }}</span>
                    @if($strategicMatch)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200 flex-shrink-0">
                            {{ $strategicMatch['name'] }}
                        </span>
                    @endif
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-lime-50 text-lime-700 tabular-nums flex-shrink-0">
                        {{ $data['keyword_count'] }} KW
                    </span>
                </div>

                {{-- Metrics row --}}
                <div class="flex items-center gap-5 flex-shrink-0">
                    {{-- Keywords --}}
                    <div class="w-16 text-right">
                        <span class="text-xs font-semibold text-[var(--ui-secondary)] tabular-nums">{{ $data['keyword_count'] }}</span>
                    </div>

                    {{-- Avg Position --}}
                    <div class="w-12 flex justify-end">
                        <span class="inline-flex items-center justify-center min-w-[28px] h-5 px-1.5 rounded text-[11px] font-bold bg-{{ $posColor }}-50 text-{{ $posColor }}-700 border border-{{ $posColor }}-200 tabular-nums">
                            {{ $avgPos }}
                        </span>
                    </div>

                    {{-- Overlap --}}
                    <div class="w-16 text-right">
                        <span class="text-xs font-semibold text-[var(--ui-secondary)] tabular-nums">{{ $data['overlap_count'] }}</span>
                    </div>

                    {{-- Gaps --}}
                    <div class="w-12 flex justify-end">
                        <span class="inline-flex items-center justify-center min-w-[28px] h-5 px-1.5 rounded text-[11px] font-bold bg-{{ $gapColor }}-50 text-{{ $gapColor }}-700 border border-{{ $gapColor }}-200 tabular-nums">
                            {{ $gapCount }}
                        </span>
                    </div>

                    {{-- Visibility --}}
                    <div class="w-20 text-right">
                        <span class="text-xs font-extrabold text-lime-700 tabular-nums">{{ number_format($data['visibility_score']) }}</span>
                    </div>
                </div>
            </div>

            {{-- Mobile/Tablet: stacked layout --}}
            <div class="lg:hidden">
                {{-- Header row --}}
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg transition-all duration-200"
                          :class="expanded ? 'bg-lime-50 rotate-90' : ''">
                        @svg('heroicon-o-chevron-right', 'w-3.5 h-3.5 text-[var(--ui-muted)]')
                    </span>
                    @if($strategicMatch && $strategicMatch['logo_url'])
                        <img src="{{ $strategicMatch['logo_url'] }}" alt="" class="w-5 h-5 rounded-full object-cover flex-shrink-0">
                    @else
                        @svg('heroicon-o-globe-alt', 'w-4 h-4 text-lime-500 flex-shrink-0')
                    @endif
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-semibold text-[var(--ui-secondary)] truncate block">{{ $domain }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        @if($strategicMatch)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                {{ $strategicMatch['name'] }}
                            </span>
                        @endif
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-lime-50 text-lime-700 tabular-nums">
                            {{ $data['keyword_count'] }}
                        </span>
                    </div>
                </div>

                {{-- Metrics grid --}}
                <div class="mt-2.5 ml-9 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2">
                    <div>
                        <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">{{ "\u{00D8}" }} Pos</div>
                        <div class="mt-0.5">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-{{ $posColor }}-50 text-{{ $posColor }}-700 border border-{{ $posColor }}-200 tabular-nums">{{ $avgPos }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Overlap</div>
                        <div class="text-xs font-semibold text-[var(--ui-secondary)] tabular-nums mt-0.5">{{ $data['overlap_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Gaps</div>
                        <div class="mt-0.5">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-{{ $gapColor }}-50 text-{{ $gapColor }}-700 border border-{{ $gapColor }}-200 tabular-nums">{{ $gapCount }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Visibility</div>
                        <div class="text-xs font-extrabold text-lime-700 tabular-nums mt-0.5">{{ number_format($data['visibility_score']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Expanded: Keyword list --}}
    <div x-show="expanded" x-collapse.duration.200ms>
        <div class="mt-1 rounded-xl border border-lime-200/60 bg-gradient-to-b from-lime-50/30 to-white overflow-hidden">

            {{-- Keyword table header (desktop) --}}
            <div class="hidden md:grid grid-cols-12 gap-2 px-5 py-2.5 bg-lime-50/60 border-b border-lime-100/60">
                <div class="col-span-4 text-[10px] font-semibold uppercase tracking-wider text-lime-700">Keyword</div>
                <div class="col-span-2 text-[10px] font-semibold uppercase tracking-wider text-lime-700 text-right">Ihre Pos</div>
                <div class="col-span-2 text-[10px] font-semibold uppercase tracking-wider text-lime-700 text-right">Unsere Pos</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-lime-700 text-right">SV</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-lime-700 text-center">Intent</div>
                <div class="col-span-2 text-[10px] font-semibold uppercase tracking-wider text-lime-700 text-center">Status</div>
            </div>

            {{-- Keyword rows --}}
            <div class="divide-y divide-[var(--ui-border)]/20">
                @foreach($keywords as $kw)
                    @php
                        $theirPos = $kw['their_position'];
                        $ourPos = $kw['our_position'];
                        $isGap = $ourPos === null && $theirPos !== null;

                        $theirPosColor = $theirPos !== null
                            ? ($theirPos <= 3 ? 'emerald' : ($theirPos <= 10 ? 'lime' : ($theirPos <= 20 ? 'yellow' : ($theirPos <= 50 ? 'orange' : 'red'))))
                            : 'gray';
                        $ourPosColor = $ourPos !== null
                            ? ($ourPos <= 3 ? 'emerald' : ($ourPos <= 10 ? 'lime' : ($ourPos <= 20 ? 'yellow' : ($ourPos <= 50 ? 'orange' : 'red'))))
                            : 'gray';

                        $intentConfig = [
                            'informational' => ['label' => 'Info', 'color' => 'blue'],
                            'navigational' => ['label' => 'Navi', 'color' => 'purple'],
                            'commercial' => ['label' => 'Kommerz.', 'color' => 'amber'],
                            'transactional' => ['label' => 'Transakt.', 'color' => 'green'],
                        ];
                        $kwIntent = $intentConfig[$kw['search_intent']] ?? null;
                    @endphp

                    {{-- Desktop row --}}
                    <div class="hidden md:grid grid-cols-12 gap-2 items-center px-5 py-2 transition-colors {{ $isGap ? 'bg-red-50/40 hover:bg-red-50/60' : 'hover:bg-white/80' }}">
                        <div class="col-span-4 text-xs font-medium text-[var(--ui-secondary)] truncate">{{ $kw['keyword'] }}</div>
                        <div class="col-span-2 flex justify-end">
                            @if($theirPos !== null)
                                <span class="inline-flex items-center justify-center min-w-[24px] h-5 px-1 rounded text-[10px] font-bold bg-{{ $theirPosColor }}-50 text-{{ $theirPosColor }}-700 border border-{{ $theirPosColor }}-200 tabular-nums">{{ $theirPos }}</span>
                            @else
                                <span class="text-xs text-[var(--ui-muted)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                        <div class="col-span-2 flex justify-end items-center gap-1.5">
                            @if($ourPos !== null)
                                <span class="inline-flex items-center justify-center min-w-[24px] h-5 px-1 rounded text-[10px] font-bold bg-{{ $ourPosColor }}-50 text-{{ $ourPosColor }}-700 border border-{{ $ourPosColor }}-200 tabular-nums">{{ $ourPos }}</span>
                            @else
                                <span class="text-xs text-[var(--ui-muted)]">{{ "\u{2013}" }}</span>
                            @endif
                            @if($isGap)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-700 border border-red-200 uppercase tracking-wide">GAP</span>
                            @endif
                        </div>
                        <div class="col-span-1 text-xs tabular-nums text-[var(--ui-secondary)] text-right">
                            {{ $kw['search_volume'] > 0 ? number_format($kw['search_volume']) : "\u{2013}" }}
                        </div>
                        <div class="col-span-1 flex justify-center">
                            @if($kwIntent)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-{{ $kwIntent['color'] }}-50 text-{{ $kwIntent['color'] }}-700 border border-{{ $kwIntent['color'] }}-200">{{ $kwIntent['label'] }}</span>
                            @else
                                <span class="text-xs text-[var(--ui-muted)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                        <div class="col-span-2 flex justify-center">
                            @if($kw['url'])
                                <a href="{{ $kw['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 text-gray-600 border border-gray-200 hover:bg-gray-100 transition-colors truncate max-w-full" @click.stop>
                                    @svg('heroicon-o-arrow-top-right-on-square', 'w-3 h-3 flex-shrink-0')
                                    <span class="truncate">URL</span>
                                </a>
                            @else
                                <span class="text-xs text-[var(--ui-muted)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Mobile card --}}
                    <div class="md:hidden px-4 py-3 {{ $isGap ? 'bg-red-50/40' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-xs font-semibold text-[var(--ui-secondary)]">{{ $kw['keyword'] }}</span>
                            @if($isGap)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-700 border border-red-200 uppercase tracking-wide flex-shrink-0">GAP</span>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            @if($theirPos !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] bg-{{ $theirPosColor }}-50 text-{{ $theirPosColor }}-700 border border-{{ $theirPosColor }}-200">
                                    <span class="text-[var(--ui-muted)]">Ihre</span>
                                    <span class="font-bold tabular-nums">{{ $theirPos }}</span>
                                </span>
                            @endif
                            @if($ourPos !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] bg-{{ $ourPosColor }}-50 text-{{ $ourPosColor }}-700 border border-{{ $ourPosColor }}-200">
                                    <span class="text-[var(--ui-muted)]">Unsere</span>
                                    <span class="font-bold tabular-nums">{{ $ourPos }}</span>
                                </span>
                            @endif
                            @if($kw['search_volume'] > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-50 border border-gray-200 text-[10px]">
                                    <span class="text-[var(--ui-muted)]">SV</span>
                                    <span class="font-bold text-[var(--ui-secondary)] tabular-nums">{{ number_format($kw['search_volume']) }}</span>
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
            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-2.5 bg-lime-50/40 border-t border-lime-100/60">
                <span class="text-[10px] font-medium text-lime-700">{{ $data['keyword_count'] }} Keywords</span>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] text-lime-600">
                    <span>Overlap: <strong class="text-lime-700">{{ $data['overlap_count'] }}</strong></span>
                    <span>Gaps: <strong class="text-{{ $gapColor }}-700">{{ $gapCount }}</strong></span>
                    <span>Visibility: <strong class="text-lime-700">{{ number_format($data['visibility_score']) }}</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>
