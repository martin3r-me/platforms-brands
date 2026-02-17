<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$guidelineBoard->name" icon="heroicon-o-book-open">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $guidelineBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
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
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-100 to-cyan-50 flex items-center justify-center">
                        @svg('heroicon-o-book-open', 'w-6 h-6 text-cyan-600')
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">{{ $guidelineBoard->name }}</h1>
                        @if($guidelineBoard->description)
                            <p class="text-[var(--ui-muted)] mt-1">{{ $guidelineBoard->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-8">
            {{-- Table of Contents (Inhaltsverzeichnis) --}}
            @if($chapters->count() > 0)
                <div class="hidden lg:block w-64 flex-shrink-0">
                    <div class="sticky top-24">
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-4">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Inhaltsverzeichnis</h3>
                            <nav class="space-y-1">
                                @foreach($chapters as $index => $chapter)
                                    <a href="#chapter-{{ $chapter->id }}" class="flex items-center gap-2 px-3 py-2 text-sm text-[var(--ui-secondary)] hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors">
                                        <span class="text-xs font-bold text-[var(--ui-muted)] w-5">{{ $index + 1 }}.</span>
                                        <span class="truncate">{{ $chapter->title }}</span>
                                        @if($chapter->entries->count() > 0)
                                            <span class="ml-auto text-[10px] font-medium px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-600">{{ $chapter->entries->count() }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Main Content --}}
            <div class="flex-1 min-w-0 space-y-6">
                @if($chapters->count() > 0)
                    @foreach($chapters as $chapterIndex => $chapter)
                        <div id="chapter-{{ $chapter->id }}" class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden scroll-mt-24">
                            {{-- Chapter Header --}}
                            <div class="p-6 lg:p-8 border-b border-[var(--ui-border)]/40">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-100 to-cyan-50 flex items-center justify-center">
                                            @if($chapter->icon)
                                                @svg($chapter->icon, 'w-5 h-5 text-cyan-600')
                                            @else
                                                <span class="text-sm font-bold text-cyan-600">{{ $chapterIndex + 1 }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-bold text-[var(--ui-secondary)]">{{ $chapter->title }}</h2>
                                            @if($chapter->description)
                                                <p class="text-sm text-[var(--ui-muted)] mt-0.5">{{ $chapter->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @can('update', $guidelineBoard)
                                        <div class="flex items-center gap-1">
                                            <button
                                                x-data
                                                @click="$dispatch('open-modal-guideline-entry', { guidelineBoardId: {{ $guidelineBoard->id }}, chapterId: {{ $chapter->id }} })"
                                                class="p-2 text-[var(--ui-muted)] hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors"
                                                title="Regel hinzuf&uuml;gen"
                                            >
                                                @svg('heroicon-o-plus', 'w-5 h-5')
                                            </button>
                                            <button
                                                x-data
                                                @click="$dispatch('open-modal-guideline-chapter', { guidelineBoardId: {{ $guidelineBoard->id }}, chapterId: {{ $chapter->id }} })"
                                                class="p-2 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-[var(--ui-primary-5)] rounded-lg transition-colors"
                                                title="Kapitel bearbeiten"
                                            >
                                                @svg('heroicon-o-pencil', 'w-4 h-4')
                                            </button>
                                            <button
                                                wire:click="deleteChapter({{ $chapter->id }})"
                                                wire:confirm="Kapitel und alle enthaltenen Regeln wirklich l&ouml;schen?"
                                                class="p-2 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Kapitel l&ouml;schen"
                                            >
                                                @svg('heroicon-o-trash', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endcan
                                </div>
                            </div>

                            {{-- Chapter Entries --}}
                            <div class="p-6 lg:p-8">
                                @if($chapter->entries->count() > 0)
                                    <div class="space-y-8">
                                        @foreach($chapter->entries as $entry)
                                            <div class="group relative">
                                                {{-- Entry Header --}}
                                                <div class="flex items-start justify-between mb-4">
                                                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $entry->title }}</h3>
                                                    @can('update', $guidelineBoard)
                                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <button
                                                                x-data
                                                                @click="$dispatch('open-modal-guideline-entry', { guidelineBoardId: {{ $guidelineBoard->id }}, chapterId: {{ $chapter->id }}, entryId: {{ $entry->id }} })"
                                                                class="p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-[var(--ui-primary-5)] rounded transition-colors"
                                                                title="Bearbeiten"
                                                            >
                                                                @svg('heroicon-o-pencil', 'w-4 h-4')
                                                            </button>
                                                            <button
                                                                wire:click="deleteEntry({{ $entry->id }})"
                                                                wire:confirm="Regel wirklich l&ouml;schen?"
                                                                class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                                                                title="L&ouml;schen"
                                                            >
                                                                @svg('heroicon-o-trash', 'w-4 h-4')
                                                            </button>
                                                        </div>
                                                    @endcan
                                                </div>

                                                {{-- Rule Text --}}
                                                <div class="p-4 rounded-xl bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 mb-4">
                                                    <p class="text-sm text-[var(--ui-secondary)] leading-relaxed">{{ $entry->rule_text }}</p>
                                                </div>

                                                {{-- Rationale --}}
                                                @if($entry->rationale)
                                                    <div class="flex items-start gap-2 mb-4 px-4">
                                                        @svg('heroicon-o-light-bulb', 'w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0')
                                                        <p class="text-sm text-[var(--ui-muted)] italic">{{ $entry->rationale }}</p>
                                                    </div>
                                                @endif

                                                {{-- Do / Don't Comparison --}}
                                                @if($entry->do_example || $entry->dont_example)
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                                        {{-- DO --}}
                                                        @if($entry->do_example)
                                                            <div class="rounded-xl border-2 border-green-200 bg-green-50/50 overflow-hidden">
                                                                <div class="flex items-center gap-2 px-4 py-2.5 bg-green-100/80 border-b border-green-200">
                                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-500">
                                                                        @svg('heroicon-o-check', 'w-4 h-4 text-white')
                                                                    </span>
                                                                    <span class="text-sm font-bold text-green-800 uppercase tracking-wider">Do</span>
                                                                </div>
                                                                @if($entry->do_image_path)
                                                                    <div class="px-4 pt-3">
                                                                        <img src="{{ asset($entry->do_image_path) }}" alt="Do-Beispiel" class="w-full rounded-lg border border-green-200">
                                                                    </div>
                                                                @endif
                                                                <div class="p-4">
                                                                    <p class="text-sm text-green-900 leading-relaxed">{{ $entry->do_example }}</p>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        {{-- DON'T --}}
                                                        @if($entry->dont_example)
                                                            <div class="rounded-xl border-2 border-red-200 bg-red-50/50 overflow-hidden">
                                                                <div class="flex items-center gap-2 px-4 py-2.5 bg-red-100/80 border-b border-red-200">
                                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-500">
                                                                        @svg('heroicon-o-x-mark', 'w-4 h-4 text-white')
                                                                    </span>
                                                                    <span class="text-sm font-bold text-red-800 uppercase tracking-wider">Don't</span>
                                                                </div>
                                                                @if($entry->dont_image_path)
                                                                    <div class="px-4 pt-3">
                                                                        <img src="{{ asset($entry->dont_image_path) }}" alt="Don't-Beispiel" class="w-full rounded-lg border border-red-200">
                                                                    </div>
                                                                @endif
                                                                <div class="p-4">
                                                                    <p class="text-sm text-red-900 leading-relaxed">{{ $entry->dont_example }}</p>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                {{-- Cross References --}}
                                                @if(!empty($entry->cross_references))
                                                    <div class="flex flex-wrap items-center gap-2 mt-4">
                                                        @svg('heroicon-o-link', 'w-4 h-4 text-[var(--ui-muted)]')
                                                        @foreach($entry->cross_references as $ref)
                                                            @php
                                                                $refRoute = null;
                                                                $refBoardType = $ref['board_type'] ?? '';
                                                                $refBoardId = $ref['board_id'] ?? '';
                                                                $refLabel = $ref['label'] ?? 'Board';

                                                                $routeMap = [
                                                                    'ci-board' => 'brands.ci-boards.show',
                                                                    'logo-board' => 'brands.logo-boards.show',
                                                                    'typography-board' => 'brands.typography-boards.show',
                                                                    'tone-of-voice-board' => 'brands.tone-of-voice-boards.show',
                                                                    'persona-board' => 'brands.persona-boards.show',
                                                                    'competitor-board' => 'brands.competitor-boards.show',
                                                                ];

                                                                $modelMap = [
                                                                    'ci-board' => \Platform\Brands\Models\BrandsCiBoard::class,
                                                                    'logo-board' => \Platform\Brands\Models\BrandsLogoBoard::class,
                                                                    'typography-board' => \Platform\Brands\Models\BrandsTypographyBoard::class,
                                                                    'tone-of-voice-board' => \Platform\Brands\Models\BrandsToneOfVoiceBoard::class,
                                                                    'persona-board' => \Platform\Brands\Models\BrandsPersonaBoard::class,
                                                                    'competitor-board' => \Platform\Brands\Models\BrandsCompetitorBoard::class,
                                                                ];

                                                                if (isset($routeMap[$refBoardType]) && $refBoardId) {
                                                                    $modelClass = $modelMap[$refBoardType];
                                                                    $refModel = $modelClass::find($refBoardId);
                                                                    if ($refModel) {
                                                                        $refRoute = route($routeMap[$refBoardType], $refModel);
                                                                        $refLabel = $refLabel ?: $refModel->name;
                                                                    }
                                                                }
                                                            @endphp
                                                            @if($refRoute)
                                                                <a href="{{ $refRoute }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-cyan-700 bg-cyan-50 border border-cyan-200 rounded-md hover:bg-cyan-100 transition-colors">
                                                                    @svg('heroicon-o-arrow-top-right-on-square', 'w-3 h-3')
                                                                    {{ $refLabel }}
                                                                </a>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif

                                                {{-- Separator --}}
                                                @if(!$loop->last)
                                                    <div class="border-t border-[var(--ui-border)]/30 mt-8"></div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-cyan-50 mb-3">
                                            @svg('heroicon-o-document-text', 'w-6 h-6 text-cyan-400')
                                        </div>
                                        <p class="text-sm text-[var(--ui-muted)] mb-3">Noch keine Regeln in diesem Kapitel</p>
                                        @can('update', $guidelineBoard)
                                            <x-ui-button
                                                variant="primary"
                                                size="sm"
                                                x-data
                                                @click="$dispatch('open-modal-guideline-entry', { guidelineBoardId: {{ $guidelineBoard->id }}, chapterId: {{ $chapter->id }} })"
                                            >
                                                <span class="inline-flex items-center gap-2">
                                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                                    <span>Regel hinzuf&uuml;gen</span>
                                                </span>
                                            </x-ui-button>
                                        @endcan
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-16 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-cyan-50 mb-4">
                            @svg('heroicon-o-book-open', 'w-8 h-8 text-cyan-400')
                        </div>
                        <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Kapitel vorhanden</p>
                        <p class="text-xs text-[var(--ui-muted)] mb-4">Erstelle Kapitel, um deine Markenregeln zu strukturieren</p>
                        @can('update', $guidelineBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-guideline-chapter', { guidelineBoardId: {{ $guidelineBoard->id }} })"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Kapitel hinzuf&uuml;gen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-&Uuml;bersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $guidelineBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zur&uuml;ck zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $guidelineBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-guideline-chapter', { guidelineBoardId: {{ $guidelineBoard->id }} })"
                                class="w-full"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Kapitel hinzuf&uuml;gen</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-guideline-board-settings', { guidelineBoardId: {{ $guidelineBoard->id }} })" class="w-full">
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
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-cyan-50 text-cyan-600 border border-cyan-200">
                                Guidelines
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $guidelineBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($chapters->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Kapitel</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $chapters->count() }}
                                </span>
                            </div>
                        @endif
                        @php
                            $totalEntries = $chapters->sum(fn($ch) => $ch->entries->count());
                        @endphp
                        @if($totalEntries > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Regeln</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $totalEntries }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Kapitel-Übersicht --}}
                @if($chapters->count() > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Kapitel</h3>
                        <div class="space-y-2">
                            @foreach($chapters as $chapter)
                                <a href="#chapter-{{ $chapter->id }}" class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg hover:bg-cyan-50 hover:border-cyan-200 transition-colors">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $chapter->title }}</span>
                                    <span class="text-xs font-medium px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-600">{{ $chapter->entries->count() }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
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

    <livewire:brands.guideline-board-settings-modal />
    <livewire:brands.guideline-chapter-modal />
    <livewire:brands.guideline-entry-modal />
</x-ui-page>
