<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$guidelineBoard->name" icon="heroicon-o-book-open" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $guidelineBoard->brand->name, 'href' => route('brands.brands.show', $guidelineBoard->brand)],
            ['label' => $guidelineBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $guidelineBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-guideline-board-settings', { guidelineBoardId: {{ $guidelineBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
            </x-slot>

            @can('update', $guidelineBoard)
                <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-guideline-chapter', { guidelineBoardId: {{ $guidelineBoard->id }} })">
                    @svg('heroicon-o-plus', 'w-4 h-4') Kapitel hinzufügen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $guidelineBoard->name }}</h1>
            @if($guidelineBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $guidelineBoard->description }}</p>
            @endif
        </div>

        <div class="flex gap-8">
            {{-- Inhaltsverzeichnis --}}
            @if($chapters->count() > 0)
                <div class="hidden lg:block w-60 flex-shrink-0">
                    <div class="sticky top-24">
                        <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Inhaltsverzeichnis</h3>
                        <nav class="space-y-0.5">
                            @foreach($chapters as $index => $chapter)
                                <a href="#chapter-{{ $chapter->id }}" class="flex items-center gap-2 rounded-[6px] px-2.5 py-1.5 text-[13px] text-[color:var(--nx-muted)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]">
                                    <span class="w-4 shrink-0 text-[11px] tabular-nums text-[color:var(--nx-faint)]">{{ $index + 1 }}.</span>
                                    <span class="truncate">{{ $chapter->title }}</span>
                                    @if($chapter->entries->count() > 0)
                                        <span class="ml-auto text-[11px] tabular-nums text-[color:var(--nx-faint)]">{{ $chapter->entries->count() }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </div>
            @endif

            {{-- Hauptinhalt --}}
            <div class="min-w-0 flex-1 space-y-10">
                @if($chapters->count() > 0)
                    @foreach($chapters as $chapterIndex => $chapter)
                        <section id="chapter-{{ $chapter->id }}" class="scroll-mt-24 space-y-3">
                            {{-- Kapitel-Kopf --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    @if($chapter->icon)
                                        @svg($chapter->icon, 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                                    @else
                                        <span class="w-4 shrink-0 text-sm font-semibold tabular-nums text-[color:var(--nx-faint)]">{{ $chapterIndex + 1 }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <h2 class="truncate text-sm font-semibold text-[color:var(--nx-text)]">{{ $chapter->title }}</h2>
                                        @if($chapter->description)
                                            <p class="mt-0.5 text-xs text-[color:var(--nx-faint)]">{{ $chapter->description }}</p>
                                        @endif
                                    </div>
                                </div>
                                @can('update', $guidelineBoard)
                                    <div class="flex shrink-0 items-center gap-1">
                                        <button type="button" x-data @click="$dispatch('open-modal-guideline-entry', { guidelineBoardId: {{ $guidelineBoard->id }}, chapterId: {{ $chapter->id }} })"
                                                class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]" title="Regel hinzufügen">
                                            @svg('heroicon-o-plus', 'w-4 h-4')
                                        </button>
                                        <button type="button" x-data @click="$dispatch('open-modal-guideline-chapter', { guidelineBoardId: {{ $guidelineBoard->id }}, chapterId: {{ $chapter->id }} })"
                                                class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]" title="Kapitel bearbeiten">
                                            @svg('heroicon-o-pencil', 'w-4 h-4')
                                        </button>
                                        <button type="button" wire:click="deleteChapter({{ $chapter->id }})" wire:confirm="Kapitel und alle enthaltenen Regeln wirklich löschen?"
                                                class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-danger)]" title="Kapitel löschen">
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                        </button>
                                    </div>
                                @endcan
                            </div>

                            {{-- Kapitel-Regeln als Hairline-Liste --}}
                            @if($chapter->entries->count() > 0)
                                <x-nx-card flush class="divide-y divide-[color:var(--nx-line)]">
                                    @foreach($chapter->entries as $entry)
                                        <div class="group p-5">
                                            {{-- Regel-Kopf --}}
                                            <div class="flex items-start justify-between gap-3">
                                                <h3 class="text-[15px] font-semibold text-[color:var(--nx-text)]">{{ $entry->title }}</h3>
                                                @can('update', $guidelineBoard)
                                                    <div class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                                        <button type="button" x-data @click="$dispatch('open-modal-guideline-entry', { guidelineBoardId: {{ $guidelineBoard->id }}, chapterId: {{ $chapter->id }}, entryId: {{ $entry->id }} })"
                                                                class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]" title="Bearbeiten">
                                                            @svg('heroicon-o-pencil', 'w-4 h-4')
                                                        </button>
                                                        <button type="button" wire:click="deleteEntry({{ $entry->id }})" wire:confirm="Regel wirklich löschen?"
                                                                class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-danger)]" title="Löschen">
                                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                                        </button>
                                                    </div>
                                                @endcan
                                            </div>

                                            {{-- Regeltext --}}
                                            <p class="mt-2 text-[13px] leading-relaxed text-[color:var(--nx-text)]">{{ $entry->rule_text }}</p>

                                            {{-- Begründung --}}
                                            @if($entry->rationale)
                                                <div class="mt-2 flex items-start gap-2">
                                                    @svg('heroicon-o-light-bulb', 'w-4 h-4 mt-0.5 shrink-0 text-[color:var(--nx-faint)]')
                                                    <p class="text-[13px] italic text-[color:var(--nx-muted)]">{{ $entry->rationale }}</p>
                                                </div>
                                            @endif

                                            {{-- Do / Don't --}}
                                            @if($entry->do_example || $entry->dont_example)
                                                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    {{-- DO --}}
                                                    @if($entry->do_example)
                                                        <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-4">
                                                            <x-nx-badge variant="success" dot>Do</x-nx-badge>
                                                            @if($entry->do_image_path)
                                                                <img src="{{ asset($entry->do_image_path) }}" alt="Do-Beispiel" class="mt-3 w-full rounded-[6px] border border-[color:var(--nx-line)]">
                                                            @endif
                                                            <p class="mt-3 text-[13px] leading-relaxed text-[color:var(--nx-text)]">{{ $entry->do_example }}</p>
                                                        </div>
                                                    @endif

                                                    {{-- DON'T --}}
                                                    @if($entry->dont_example)
                                                        <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-4">
                                                            <x-nx-badge variant="danger" dot>Don't</x-nx-badge>
                                                            @if($entry->dont_image_path)
                                                                <img src="{{ asset($entry->dont_image_path) }}" alt="Don't-Beispiel" class="mt-3 w-full rounded-[6px] border border-[color:var(--nx-line)]">
                                                            @endif
                                                            <p class="mt-3 text-[13px] leading-relaxed text-[color:var(--nx-text)]">{{ $entry->dont_example }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            {{-- Querverweise --}}
                                            @if(!empty($entry->cross_references))
                                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                                    @svg('heroicon-o-link', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
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
                                                            <x-nx-badge :href="$refRoute" variant="info">
                                                                @svg('heroicon-o-arrow-top-right-on-square', 'w-3 h-3')
                                                                {{ $refLabel }}
                                                            </x-nx-badge>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </x-nx-card>
                            @else
                                <x-nx-empty icon="heroicon-o-document-text">
                                    Noch keine Regeln in diesem Kapitel
                                    @can('update', $guidelineBoard)
                                        <x-slot name="action">
                                            <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-guideline-entry', { guidelineBoardId: {{ $guidelineBoard->id }}, chapterId: {{ $chapter->id }} })">
                                                @svg('heroicon-o-plus', 'w-4 h-4') Regel hinzufügen
                                            </x-nx-button>
                                        </x-slot>
                                    @endcan
                                </x-nx-empty>
                            @endif
                        </section>
                    @endforeach
                @else
                    <x-nx-empty icon="heroicon-o-book-open">
                        Noch keine Kapitel vorhanden – erstelle Kapitel, um deine Markenregeln zu strukturieren.
                        @can('update', $guidelineBoard)
                            <x-slot name="action">
                                <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-guideline-chapter', { guidelineBoardId: {{ $guidelineBoard->id }} })">
                                    @svg('heroicon-o-plus', 'w-4 h-4') Kapitel hinzufügen
                                </x-nx-button>
                            </x-slot>
                        @endcan
                    </x-nx-empty>
                @endif
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        @php
            $totalEntries = $chapters->sum(fn($ch) => $ch->entries->count());
        @endphp
        @include('brands::partials.board-sidebar', ['detailRows' => array_filter([
            ['label' => 'Typ', 'value' => 'Guidelines'],
            ['label' => 'Erstellt', 'value' => $guidelineBoard->created_at->format('d.m.Y')],
            $chapters->count() > 0 ? ['label' => 'Kapitel', 'value' => (string) $chapters->count()] : null,
            $totalEntries > 0 ? ['label' => 'Regeln', 'value' => (string) $totalEntries] : null,
        ])])
    </x-slot>

    <x-slot name="activity">
        @include('brands::partials.board-activity')
    </x-slot>

    <livewire:brands.guideline-board-settings-modal />
    <livewire:brands.guideline-chapter-modal />
    <livewire:brands.guideline-entry-modal />
</x-ui-page>
