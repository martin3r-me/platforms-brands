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
    {{-- Domain Row (ruhiger Hairline-Block) --}}
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

                {{-- Domain name + badges --}}
                <div class="flex-1 min-w-0 flex items-center gap-2.5">
                    @if($strategicMatch && $strategicMatch['logo_url'])
                        <img src="{{ $strategicMatch['logo_url'] }}" alt="" class="w-5 h-5 rounded-full object-cover flex-shrink-0">
                    @else
                        @svg('heroicon-o-globe-alt', 'w-4 h-4 text-[color:var(--nx-faint)] flex-shrink-0')
                    @endif
                    <span class="text-sm font-semibold text-[color:var(--nx-text)] truncate">{{ $domain }}</span>
                    @if($strategicMatch)
                        <x-nx-badge variant="warning" class="flex-shrink-0">{{ $strategicMatch['name'] }}</x-nx-badge>
                    @endif
                    <x-nx-badge class="flex-shrink-0 tabular-nums">{{ $data['keyword_count'] }} KW</x-nx-badge>
                </div>

                {{-- Metrics row --}}
                <div class="flex items-center gap-5 flex-shrink-0">
                    {{-- Keywords --}}
                    <div class="w-16 text-right">
                        <span class="text-xs font-semibold text-[color:var(--nx-text)] tabular-nums">{{ $data['keyword_count'] }}</span>
                    </div>

                    {{-- Avg Position --}}
                    <div class="w-12 flex justify-end">
                        <x-nx-badge :variant="$nxVariant($posColor)" class="tabular-nums">{{ $avgPos }}</x-nx-badge>
                    </div>

                    {{-- Overlap --}}
                    <div class="w-16 text-right">
                        <span class="text-xs font-semibold text-[color:var(--nx-text)] tabular-nums">{{ $data['overlap_count'] }}</span>
                    </div>

                    {{-- Gaps --}}
                    <div class="w-12 flex justify-end">
                        <x-nx-badge :variant="$nxVariant($gapColor)" class="tabular-nums">{{ $gapCount }}</x-nx-badge>
                    </div>

                    {{-- Visibility --}}
                    <div class="w-20 text-right">
                        <span class="text-xs font-extrabold text-[color:var(--nx-text)] tabular-nums">{{ number_format($data['visibility_score']) }}</span>
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
                    @if($strategicMatch && $strategicMatch['logo_url'])
                        <img src="{{ $strategicMatch['logo_url'] }}" alt="" class="w-5 h-5 rounded-full object-cover flex-shrink-0">
                    @else
                        @svg('heroicon-o-globe-alt', 'w-4 h-4 text-[color:var(--nx-faint)] flex-shrink-0')
                    @endif
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-semibold text-[color:var(--nx-text)] truncate block">{{ $domain }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        @if($strategicMatch)
                            <x-nx-badge variant="warning">{{ $strategicMatch['name'] }}</x-nx-badge>
                        @endif
                        <x-nx-badge class="tabular-nums">{{ $data['keyword_count'] }}</x-nx-badge>
                    </div>
                </div>

                {{-- Metrics grid --}}
                <div class="mt-2.5 ml-9 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-2">
                    <div>
                        <div class="text-[10px] text-[color:var(--nx-faint)] uppercase tracking-wide">{{ "\u{00D8}" }} Pos</div>
                        <div class="mt-0.5">
                            <x-nx-badge :variant="$nxVariant($posColor)" class="tabular-nums">{{ $avgPos }}</x-nx-badge>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[color:var(--nx-faint)] uppercase tracking-wide">Overlap</div>
                        <div class="text-xs font-semibold text-[color:var(--nx-text)] tabular-nums mt-0.5">{{ $data['overlap_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[color:var(--nx-faint)] uppercase tracking-wide">Gaps</div>
                        <div class="mt-0.5">
                            <x-nx-badge :variant="$nxVariant($gapColor)" class="tabular-nums">{{ $gapCount }}</x-nx-badge>
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] text-[color:var(--nx-faint)] uppercase tracking-wide">Visibility</div>
                        <div class="text-xs font-extrabold text-[color:var(--nx-text)] tabular-nums mt-0.5">{{ number_format($data['visibility_score']) }}</div>
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
                <div class="col-span-2 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-right">Ihre Pos</div>
                <div class="col-span-2 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-right">Unsere Pos</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-right">SV</div>
                <div class="col-span-1 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-center">Intent</div>
                <div class="col-span-2 text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-faint)] text-center">Status</div>
            </div>

            {{-- Keyword rows --}}
            <div class="divide-y divide-[color:var(--nx-line)]">
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
                    <div class="hidden md:grid grid-cols-12 gap-2 items-center px-5 py-2 transition-colors {{ $isGap ? 'bg-[rgba(224,49,49,.06)] hover:bg-[rgba(224,49,49,.1)]' : 'hover:bg-[color:var(--nx-hover)]' }}">
                        <div class="col-span-4 text-xs font-medium text-[color:var(--nx-text)] truncate">{{ $kw['keyword'] }}</div>
                        <div class="col-span-2 flex justify-end">
                            @if($theirPos !== null)
                                <x-nx-badge :variant="$nxVariant($theirPosColor)" class="tabular-nums">{{ $theirPos }}</x-nx-badge>
                            @else
                                <span class="text-xs text-[color:var(--nx-faint)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                        <div class="col-span-2 flex justify-end items-center gap-1.5">
                            @if($ourPos !== null)
                                <x-nx-badge :variant="$nxVariant($ourPosColor)" class="tabular-nums">{{ $ourPos }}</x-nx-badge>
                            @else
                                <span class="text-xs text-[color:var(--nx-faint)]">{{ "\u{2013}" }}</span>
                            @endif
                            @if($isGap)
                                <x-nx-badge variant="danger" class="uppercase tracking-wide">GAP</x-nx-badge>
                            @endif
                        </div>
                        <div class="col-span-1 text-xs tabular-nums text-[color:var(--nx-text)] text-right">
                            {{ $kw['search_volume'] > 0 ? number_format($kw['search_volume']) : "\u{2013}" }}
                        </div>
                        <div class="col-span-1 flex justify-center">
                            @if($kwIntent)
                                <x-nx-badge :variant="$nxVariant($kwIntent['color'])">{{ $kwIntent['label'] }}</x-nx-badge>
                            @else
                                <span class="text-xs text-[color:var(--nx-faint)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                        <div class="col-span-2 flex justify-center">
                            @if($kw['url'])
                                <a href="{{ $kw['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium border border-[color:var(--nx-line)] text-[color:var(--nx-muted)] hover:bg-[color:var(--nx-hover)] transition-colors truncate max-w-full" @click.stop>
                                    @svg('heroicon-o-arrow-top-right-on-square', 'w-3 h-3 flex-shrink-0')
                                    <span class="truncate">URL</span>
                                </a>
                            @else
                                <span class="text-xs text-[color:var(--nx-faint)]">{{ "\u{2013}" }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Mobile card --}}
                    <div class="md:hidden px-4 py-3 {{ $isGap ? 'bg-[rgba(224,49,49,.06)]' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-xs font-semibold text-[color:var(--nx-text)]">{{ $kw['keyword'] }}</span>
                            @if($isGap)
                                <x-nx-badge variant="danger" class="uppercase tracking-wide flex-shrink-0">GAP</x-nx-badge>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            @if($theirPos !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border border-[color:var(--nx-line)]">
                                    <span class="text-[color:var(--nx-faint)]">Ihre</span>
                                    <span class="font-bold text-[color:var(--nx-text)] tabular-nums">{{ $theirPos }}</span>
                                </span>
                            @endif
                            @if($ourPos !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border border-[color:var(--nx-line)]">
                                    <span class="text-[color:var(--nx-faint)]">Unsere</span>
                                    <span class="font-bold text-[color:var(--nx-text)] tabular-nums">{{ $ourPos }}</span>
                                </span>
                            @endif
                            @if($kw['search_volume'] > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] border border-[color:var(--nx-line)]">
                                    <span class="text-[color:var(--nx-faint)]">SV</span>
                                    <span class="font-bold text-[color:var(--nx-text)] tabular-nums">{{ number_format($kw['search_volume']) }}</span>
                                </span>
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
                <span class="text-[10px] font-medium text-[color:var(--nx-faint)]">{{ $data['keyword_count'] }} Keywords</span>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] text-[color:var(--nx-faint)]">
                    <span>Overlap: <strong class="text-[color:var(--nx-text)]">{{ $data['overlap_count'] }}</strong></span>
                    <span>Gaps: <strong class="text-[color:var(--nx-text)]">{{ $gapCount }}</strong></span>
                    <span>Visibility: <strong class="text-[color:var(--nx-text)]">{{ number_format($data['visibility_score']) }}</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>
