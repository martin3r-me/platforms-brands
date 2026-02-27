<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$seoBoard->name" icon="heroicon-o-magnifying-glass">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $seoBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zur&uuml;ck zur Marke</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-&Uuml;bersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $seoBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zur&uuml;ck zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $seoBoard)
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-seo-board-settings', { seoBoardId: {{ $seoBoard->id }} })">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                                    <span>Einstellungen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Statistiken</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $allKeywords->count() }}</div>
                            <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Keywords</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $clusters->count() }}</div>
                            <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Cluster</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">
                                {{ $allKeywords->whereNotNull('search_volume')->avg('search_volume') ? number_format($allKeywords->whereNotNull('search_volume')->avg('search_volume'), 0) : '–' }}
                            </div>
                            <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">&empty; SV</div>
                        </div>
                        <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">
                                {{ $allKeywords->whereNotNull('position')->count() }}
                            </div>
                            <div class="text-[10px] text-[var(--ui-muted)] uppercase tracking-wide">Rankings</div>
                        </div>
                    </div>
                </div>

                {{-- Budget --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Budget</h3>
                    <div class="space-y-2">
                        @if($budgetSummary['limit_cents'] !== null)
                            <div class="bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg p-3">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs text-[var(--ui-muted)]">Verbraucht</span>
                                    <span class="text-xs font-medium text-[var(--ui-secondary)]">
                                        {{ number_format($budgetSummary['spent_cents'] / 100, 2) }} / {{ number_format($budgetSummary['limit_cents'] / 100, 2) }} &euro;
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all {{ ($budgetSummary['percentage'] ?? 0) > 80 ? 'bg-red-500' : (($budgetSummary['percentage'] ?? 0) > 50 ? 'bg-yellow-500' : 'bg-lime-500') }}"
                                         style="width: {{ min($budgetSummary['percentage'] ?? 0, 100) }}%"></div>
                                </div>
                                <div class="text-right mt-1">
                                    <span class="text-[10px] text-[var(--ui-muted)]">{{ $budgetSummary['percentage'] ?? 0 }}%</span>
                                </div>
                            </div>
                        @else
                            <div class="py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-xs text-[var(--ui-muted)]">Kein Budget-Limit gesetzt</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Letzter Refresh --}}
                @if($seoBoard->last_refreshed_at)
                    <div class="py-2 px-3 bg-lime-50 border border-lime-200 rounded-lg">
                        <span class="text-xs text-lime-700">
                            @svg('heroicon-o-clock', 'w-3 h-3 inline-block mr-1')
                            Letzter Refresh: {{ $seoBoard->last_refreshed_at->format('d.m.Y H:i') }}
                        </span>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivit&auml;ten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivit&auml;ten</div>
                <div class="space-y-3 text-sm">
                    <div class="py-8 text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                            @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                        </div>
                        <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivit&auml;ten</p>
                        <p class="text-xs text-[var(--ui-muted)] mt-1">&Auml;nderungen werden hier angezeigt</p>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Keyword Matrix --}}
    @if($allKeywords->count() > 0)
        <div class="p-4">
            {{-- Cluster Filter Chips --}}
            @if($clusters->count() > 0)
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wide mr-1">Cluster:</span>
                    @foreach($clusters as $cluster)
                        @php
                            $clusterColor = $cluster->color ?? 'gray';
                            $kwCount = $cluster->keywords->count();
                        @endphp
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $clusterColor }}-50 text-{{ $clusterColor }}-700 border border-{{ $clusterColor }}-200">
                            <span class="w-2 h-2 rounded-full bg-{{ $clusterColor }}-500"></span>
                            {{ $cluster->name }}
                            <span class="text-{{ $clusterColor }}-400 font-normal">{{ $kwCount }}</span>
                        </span>
                    @endforeach
                    @if($unclusteredKeywords->count() > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200">
                            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                            Ohne Cluster
                            <span class="text-gray-400 font-normal">{{ $unclusteredKeywords->count() }}</span>
                        </span>
                    @endif
                </div>
            @endif

            {{-- Table Container with Horizontal Scroll --}}
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="seo-matrix w-full">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-[var(--ui-border)]/60">
                                <th class="seo-matrix-sticky-col text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    Keyword
                                </th>
                                <th class="text-left px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    Cluster
                                </th>
                                <th class="text-right px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    <span class="inline-flex items-center gap-1">
                                        @svg('heroicon-o-chart-bar', 'w-3 h-3')
                                        Suchvolumen
                                    </span>
                                </th>
                                <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    Schwierigkeit
                                </th>
                                <th class="text-right px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    CPC
                                </th>
                                <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    @svg('heroicon-o-hashtag', 'w-3 h-3 inline-block')
                                    Position
                                </th>
                                <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    Intent
                                </th>
                                <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    Priorit&auml;t
                                </th>
                                <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    Trend
                                </th>
                                <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    Content
                                </th>
                                <th class="text-left px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    URL
                                </th>
                                <th class="text-center px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-[var(--ui-muted)]">
                                    @svg('heroicon-o-clock', 'w-3 h-3 inline-block')
                                    Aktualisiert
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--ui-border)]/40">
                            @foreach($allKeywords as $keyword)
                                @php
                                    $clusterColor = $keyword->cluster?->color ?? 'gray';
                                    $svPercent = $maxSearchVolume > 0 && $keyword->search_volume ? round(($keyword->search_volume / $maxSearchVolume) * 100) : 0;

                                    // KD Label + Color
                                    $kd = $keyword->keyword_difficulty;
                                    if ($kd !== null) {
                                        if ($kd <= 14) { $kdLabel = 'Sehr leicht'; $kdColor = 'emerald'; $kdBg = 'bg-emerald-50 text-emerald-700 border-emerald-200'; }
                                        elseif ($kd <= 29) { $kdLabel = 'Leicht'; $kdColor = 'green'; $kdBg = 'bg-green-50 text-green-700 border-green-200'; }
                                        elseif ($kd <= 49) { $kdLabel = 'Machbar'; $kdColor = 'yellow'; $kdBg = 'bg-yellow-50 text-yellow-700 border-yellow-200'; }
                                        elseif ($kd <= 69) { $kdLabel = 'Schwer'; $kdColor = 'orange'; $kdBg = 'bg-orange-50 text-orange-700 border-orange-200'; }
                                        elseif ($kd <= 84) { $kdLabel = 'Sehr schwer'; $kdColor = 'red'; $kdBg = 'bg-red-50 text-red-700 border-red-200'; }
                                        else { $kdLabel = 'Extrem'; $kdColor = 'rose'; $kdBg = 'bg-rose-100 text-rose-800 border-rose-300'; }
                                    } else {
                                        $kdLabel = null; $kdColor = 'gray'; $kdBg = '';
                                    }

                                    // Intent
                                    $intentConfig = [
                                        'informational' => ['label' => 'Info', 'color' => 'blue', 'icon' => 'heroicon-o-information-circle'],
                                        'navigational' => ['label' => 'Navi', 'color' => 'purple', 'icon' => 'heroicon-o-map-pin'],
                                        'commercial' => ['label' => 'Komm.', 'color' => 'amber', 'icon' => 'heroicon-o-shopping-bag'],
                                        'transactional' => ['label' => 'Trans.', 'color' => 'green', 'icon' => 'heroicon-o-banknotes'],
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

                                    // URL display
                                    $displayUrl = $keyword->published_url ?? $keyword->target_url;
                                @endphp
                                <tr class="group hover:bg-lime-50/30 transition-colors">
                                    {{-- Keyword (sticky) --}}
                                    <td class="seo-matrix-sticky-col px-4 py-2.5">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-sm font-medium text-[var(--ui-secondary)] group-hover:text-lime-700 transition-colors">
                                                {{ $keyword->keyword }}
                                            </span>
                                            @if($keyword->content_idea)
                                                <span class="text-[10px] text-[var(--ui-muted)] line-clamp-1 max-w-[250px]">
                                                    {{ Str::limit($keyword->content_idea, 50) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Cluster --}}
                                    <td class="px-3 py-2.5">
                                        @if($keyword->cluster)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-{{ $clusterColor }}-50 text-{{ $clusterColor }}-700 border border-{{ $clusterColor }}-200 whitespace-nowrap">
                                                <span class="w-1.5 h-1.5 rounded-full bg-{{ $clusterColor }}-500"></span>
                                                {{ Str::limit($keyword->cluster->name, 20) }}
                                            </span>
                                        @else
                                            <span class="text-[10px] text-[var(--ui-muted)]">&ndash;</span>
                                        @endif
                                    </td>

                                    {{-- Search Volume --}}
                                    <td class="px-3 py-2.5">
                                        @if($keyword->search_volume !== null)
                                            <div class="flex items-center gap-2 justify-end">
                                                <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full transition-all bg-lime-500" style="width: {{ $svPercent }}%"></div>
                                                </div>
                                                <span class="text-xs font-semibold text-[var(--ui-secondary)] tabular-nums min-w-[40px] text-right">
                                                    {{ number_format($keyword->search_volume) }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-xs text-[var(--ui-muted)] block text-right">&ndash;</span>
                                        @endif
                                    </td>

                                    {{-- Keyword Difficulty --}}
                                    <td class="px-3 py-2.5 text-center">
                                        @if($kd !== null)
                                            <div class="inline-flex flex-col items-center gap-0.5">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $kdBg }} tabular-nums">
                                                    {{ $kd }}
                                                </span>
                                                <span class="text-[9px] text-{{ $kdColor }}-600 font-medium">{{ $kdLabel }}</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
                                        @endif
                                    </td>

                                    {{-- CPC --}}
                                    <td class="px-3 py-2.5 text-right">
                                        @if($keyword->cpc_cents !== null)
                                            <span class="text-xs font-medium text-[var(--ui-secondary)] tabular-nums">
                                                {{ number_format($keyword->cpc_cents / 100, 2) }}&thinsp;&euro;
                                            </span>
                                        @else
                                            <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
                                        @endif
                                    </td>

                                    {{-- Position --}}
                                    <td class="px-3 py-2.5 text-center">
                                        @if($keyword->position !== null)
                                            @php
                                                $posColor = $keyword->position <= 3 ? 'emerald' : ($keyword->position <= 10 ? 'lime' : ($keyword->position <= 20 ? 'yellow' : ($keyword->position <= 50 ? 'orange' : 'red')));
                                            @endphp
                                            <div class="inline-flex flex-col items-center gap-0.5">
                                                <span class="inline-flex items-center justify-center w-8 h-6 rounded text-[11px] font-bold bg-{{ $posColor }}-50 text-{{ $posColor }}-700 border border-{{ $posColor }}-200 tabular-nums">
                                                    {{ $keyword->position }}
                                                </span>
                                                @if($keyword->target_position)
                                                    <span class="text-[9px] text-[var(--ui-muted)]">Ziel: {{ $keyword->target_position }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
                                        @endif
                                    </td>

                                    {{-- Search Intent --}}
                                    <td class="px-3 py-2.5 text-center">
                                        @if($intent)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-{{ $intent['color'] }}-50 text-{{ $intent['color'] }}-700 border border-{{ $intent['color'] }}-200 whitespace-nowrap">
                                                @svg($intent['icon'], 'w-3 h-3')
                                                {{ $intent['label'] }}
                                            </span>
                                        @else
                                            <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
                                        @endif
                                    </td>

                                    {{-- Priority --}}
                                    <td class="px-3 py-2.5 text-center">
                                        @if($prio)
                                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-{{ $prio['color'] }}-50 text-{{ $prio['color'] }}-700 border border-{{ $prio['color'] }}-200 whitespace-nowrap">
                                                @svg($prio['icon'], 'w-3 h-3')
                                                {{ $prio['label'] }}
                                            </span>
                                        @else
                                            <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
                                        @endif
                                    </td>

                                    {{-- Trend --}}
                                    <td class="px-3 py-2.5 text-center">
                                        @if($trend)
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-{{ $trend['color'] }}-50">
                                                @svg($trend['icon'], 'w-3.5 h-3.5 text-' . $trend['color'] . '-600')
                                            </span>
                                        @else
                                            <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
                                        @endif
                                    </td>

                                    {{-- Content Status --}}
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-{{ $status['color'] }}-50 text-{{ $status['color'] }}-700 border border-{{ $status['color'] }}-200 whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-{{ $status['color'] }}-500"></span>
                                            {{ $status['label'] }}
                                        </span>
                                    </td>

                                    {{-- URL --}}
                                    <td class="px-3 py-2.5">
                                        @if($displayUrl)
                                            <a href="{{ $displayUrl }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-lime-700 hover:text-lime-900 hover:underline max-w-[180px] truncate" title="{{ $displayUrl }}">
                                                @svg('heroicon-o-link', 'w-3 h-3 flex-shrink-0')
                                                <span class="truncate">{{ Str::limit(parse_url($displayUrl, PHP_URL_HOST) . parse_url($displayUrl, PHP_URL_PATH), 30) }}</span>
                                            </a>
                                            @if($keyword->published_url)
                                                <span class="ml-1 inline-flex items-center px-1 py-0 rounded text-[8px] font-medium bg-green-100 text-green-700">LIVE</span>
                                            @endif
                                        @else
                                            <span class="text-xs text-[var(--ui-muted)]">&ndash;</span>
                                        @endif
                                    </td>

                                    {{-- Last Fetched --}}
                                    <td class="px-3 py-2.5 text-center">
                                        @if($keyword->last_fetched_at)
                                            <span class="text-[10px] text-[var(--ui-muted)] tabular-nums whitespace-nowrap">
                                                {{ $keyword->last_fetched_at->format('d.m.Y') }}
                                            </span>
                                        @else
                                            <span class="text-[10px] text-[var(--ui-muted)]">&ndash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="flex items-center justify-center h-full">
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center max-w-md">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-lime-50 mb-4">
                    @svg('heroicon-o-magnifying-glass', 'w-8 h-8 text-lime-600')
                </div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Keywords</h3>
                <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle Keywords und Cluster &uuml;ber die LLM-Tools, um dein SEO Board zu f&uuml;llen.</p>
                <div class="text-xs text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg p-3 border border-[var(--ui-border)]/40">
                    <p class="font-medium mb-1">Verf&uuml;gbare Tools:</p>
                    <p>brands.seo_keyword_clusters.POST</p>
                    <p>brands.seo_keywords.POST</p>
                    <p>brands.seo_keywords.BULK_POST</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Settings Modal --}}
    <livewire:brands.seo-board-settings-modal/>
</x-ui-page>

@push('styles')
<style>
    .seo-matrix {
        border-collapse: separate;
        border-spacing: 0;
    }

    .seo-matrix thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: rgb(249 250 251 / 0.95);
        backdrop-filter: blur(4px);
    }

    .seo-matrix-sticky-col {
        position: sticky;
        left: 0;
        z-index: 20;
        background: white;
        min-width: 220px;
        max-width: 300px;
    }

    .seo-matrix thead .seo-matrix-sticky-col {
        z-index: 30;
        background: rgb(249 250 251 / 0.95);
        backdrop-filter: blur(4px);
    }

    .seo-matrix tbody tr:hover .seo-matrix-sticky-col {
        background: rgb(247 254 231 / 0.3);
    }

    .seo-matrix-sticky-col::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(to right, rgba(0,0,0,0.04), transparent);
        pointer-events: none;
    }

    .seo-matrix th,
    .seo-matrix td {
        white-space: nowrap;
    }
</style>
@endpush
