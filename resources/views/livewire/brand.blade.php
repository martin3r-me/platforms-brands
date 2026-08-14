<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$brand->name" icon="heroicon-o-tag" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $brand->name],
        ]">
            {{-- Left: Settings & Export --}}
            <x-slot name="left">
                @can('update', $brand)
                    <x-ui-button variant="ghost" size="sm" @click="$dispatch('open-modal-brand-settings', { brandId: {{ $brand->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                        <span>Einstellungen</span>
                    </x-ui-button>
                @endcan
                <a href="{{ route('brands.export.show', $brand) }}" wire:navigate>
                    <x-ui-button variant="ghost" size="sm">
                        @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                        <span>Export</span>
                    </x-ui-button>
                </a>
            </x-slot>

            {{-- Right: Board erstellen Dropdown --}}
            @can('update', $brand)
                <div class="relative" x-data="{ open: false }">
                    <x-ui-button variant="primary" size="sm" @click="open = !open">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Board erstellen</span>
                        @svg('heroicon-o-chevron-down', 'w-4 h-4')
                    </x-ui-button>

                    <div
                        x-show="open"
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-[var(--ui-border)]/60 z-10 overflow-hidden"
                        style="display: none;"
                    >
                        <div class="py-1 max-h-96 overflow-y-auto">
                            <button wire:click="createSocialBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-purple-50">
                                    @svg('heroicon-o-share', 'w-4 h-4 text-purple-600')
                                </div>
                                <div>
                                    <div class="font-medium">Social Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Für Social Media</div>
                                </div>
                            </button>
                            <button wire:click="createCiBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-amber-50">
                                    @svg('heroicon-o-paint-brush', 'w-4 h-4 text-amber-600')
                                </div>
                                <div>
                                    <div class="font-medium">CI Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Für Corporate Identity</div>
                                </div>
                            </button>
                            <button wire:click="createKanbanBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-indigo-50">
                                    @svg('heroicon-o-view-columns', 'w-4 h-4 text-indigo-600')
                                </div>
                                <div>
                                    <div class="font-medium">Kanban Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Für Aufgabenverwaltung</div>
                                </div>
                            </button>
                            <button wire:click="createTypographyBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-rose-50">
                                    @svg('heroicon-o-language', 'w-4 h-4 text-rose-600')
                                </div>
                                <div>
                                    <div class="font-medium">Typografie Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Schriften & Hierarchien</div>
                                </div>
                            </button>
                            <button wire:click="createLogoBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-emerald-50">
                                    @svg('heroicon-o-photo', 'w-4 h-4 text-emerald-600')
                                </div>
                                <div>
                                    <div class="font-medium">Logo Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Logo-Varianten verwalten</div>
                                </div>
                            </button>
                            <button wire:click="createToneOfVoiceBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-violet-50">
                                    @svg('heroicon-o-megaphone', 'w-4 h-4 text-violet-600')
                                </div>
                                <div>
                                    <div class="font-medium">Tone of Voice Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Markenstimme & Messaging</div>
                                </div>
                            </button>
                            <button wire:click="createPersonaBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-teal-50">
                                    @svg('heroicon-o-user-group', 'w-4 h-4 text-teal-600')
                                </div>
                                <div>
                                    <div class="font-medium">Persona Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Zielgruppen & Personas</div>
                                </div>
                            </button>
                            <button wire:click="createCompetitorBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-orange-50">
                                    @svg('heroicon-o-scale', 'w-4 h-4 text-orange-600')
                                </div>
                                <div>
                                    <div class="font-medium">Wettbewerber Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Wettbewerber-Analyse & Positionierung</div>
                                </div>
                            </button>
                            <button wire:click="createGuidelineBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-cyan-50">
                                    @svg('heroicon-o-book-open', 'w-4 h-4 text-cyan-600')
                                </div>
                                <div>
                                    <div class="font-medium">Guidelines Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Markenregeln & Dos/Don'ts</div>
                                </div>
                            </button>
                            <button wire:click="createMoodboardBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-rose-50">
                                    @svg('heroicon-o-photo', 'w-4 h-4 text-rose-600')
                                </div>
                                <div>
                                    <div class="font-medium">Moodboard</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Bildsprache & Stilrichtung</div>
                                </div>
                            </button>
                            <button wire:click="createAssetBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-sky-50">
                                    @svg('heroicon-o-folder-open', 'w-4 h-4 text-sky-600')
                                </div>
                                <div>
                                    <div class="font-medium">Asset Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Templates & Brand Assets</div>
                                </div>
                            </button>
                            <button wire:click="createSeoBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-lime-50">
                                    @svg('heroicon-o-magnifying-glass', 'w-4 h-4 text-lime-600')
                                </div>
                                <div>
                                    <div class="font-medium">SEO Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Keyword-Recherche & SEO-Analyse</div>
                                </div>
                            </button>
                            <button wire:click="createContentBriefBoard" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-fuchsia-50">
                                    @svg('heroicon-o-document-magnifying-glass', 'w-4 h-4 text-fuchsia-600')
                                </div>
                                <div>
                                    <div class="font-medium">Content Brief Board</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Content-Planung & Briefings</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">

        @php
            $ci = $ciBoards->first();
            $primary = $ci?->primary_color;
            $slogan = $ci?->slogan ?: $ci?->tagline;
            $totalBoards = $boardGroups->sum(fn($g) => $g['boards']->count());
            $accountsCount = $facebookPages->count() + $instagramAccounts->count();
        @endphp

        {{-- ===== Header ===== --}}
        <div class="flex items-start gap-4">
            {{-- Marken-Signet --}}
            @if($primary)
                <span class="hidden sm:block h-14 w-14 shrink-0 rounded-[10px] ring-1 ring-[color:var(--nx-line)]" style="background-color: {{ $primary }};"></span>
            @else
                <span class="hidden sm:flex h-14 w-14 shrink-0 items-center justify-center rounded-[10px] bg-[color:var(--nx-accent-soft)] text-xl font-semibold text-[color:var(--nx-muted)]">{{ mb_strtoupper(mb_substr($brand->name, 0, 1)) }}</span>
            @endif

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $brand->name }}</h1>
                    @if($brand->done)
                        <x-nx-badge variant="neutral">Archiviert</x-nx-badge>
                    @else
                        <x-nx-badge variant="success" dot>Aktiv</x-nx-badge>
                    @endif
                    @if($verortung)
                        <x-nx-badge variant="info">
                            @svg('heroicon-o-building-office-2', 'w-3.5 h-3.5')
                            {{ $verortung['entity'] }}@if($verortung['type']) · {{ $verortung['type'] }}@endif
                        </x-nx-badge>
                    @else
                        <x-nx-badge variant="neutral">Unverknüpft</x-nx-badge>
                    @endif
                </div>
                @if($slogan)
                    <p class="mt-1.5 text-base italic text-[color:var(--nx-muted)]">&ldquo;{{ Str::limit($slogan, 120) }}&rdquo;</p>
                @endif
                @if($brand->description)
                    <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-[color:var(--nx-faint)]">{{ $brand->description }}</p>
                @endif
            </div>
        </div>

        {{-- ===== Kennzahlen ===== --}}
        <x-nx-stat-grid cols="4">
            <x-nx-stat label="Boards" :value="$totalBoards" :hint="$boardGroups->count() . ' Board-Typen'" icon="heroicon-o-squares-2x2" />
            <x-nx-stat label="Social Accounts" :value="$accountsCount" :hint="$facebookPages->count() . ' FB · ' . $instagramAccounts->count() . ' IG'" icon="heroicon-o-share" />
            <x-nx-stat label="Meta Connection" :value="$metaConnection ? 'Aktiv' : '—'" :hint="$metaConnection ? 'verbunden' : 'nicht verbunden'" icon="heroicon-o-link" :accent="$metaConnection ? 'var(--nx-success)' : null" />
            <x-nx-stat label="Verortung" :value="$verortung['type'] ?? 'Frei'" :hint="$verortung['entity'] ?? 'nicht verortet'" icon="heroicon-o-building-office-2" />
        </x-nx-stat-grid>

        {{-- ===== Board-Gruppen ===== --}}
        @if($boardGroups->count() > 0)
            @php
                $categories = [
                    ['label' => 'Identität', 'icon' => 'heroicon-o-swatch', 'keys' => ['ci', 'typography', 'logo', 'moodboard']],
                    ['label' => 'Strategie', 'icon' => 'heroicon-o-light-bulb', 'keys' => ['persona', 'competitor', 'tone-of-voice', 'guideline']],
                    ['label' => 'Content', 'icon' => 'heroicon-o-pencil-square', 'keys' => ['social', 'kanban', 'seo', 'content-brief']],
                    ['label' => 'Assets', 'icon' => 'heroicon-o-folder-open', 'keys' => ['asset']],
                ];
                $groupsByKey = $boardGroups->keyBy('key');
            @endphp

            @foreach($categories as $cat)
                @php($catGroups = collect($cat['keys'])->map(fn($k) => $groupsByKey->get($k))->filter())
                @if($catGroups->isNotEmpty())
                    <x-nx-section :icon="$cat['icon']" :title="$cat['label']" :hint="$catGroups->sum(fn($g) => $g['boards']->count()) . ' Boards'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($catGroups as $group)
                                @foreach($group['boards'] as $board)
                                    @php
                                        $countAttr = $group['entryRelation'] ? $group['entryRelation'] . '_count' : null;
                                        $entryCount = $countAttr && isset($board->$countAttr) ? $board->$countAttr : ($group['entryRelation'] ? $board->{$group['entryRelation']}->count() : 0);
                                        $typeLabel = trim(str_replace('Boards', '', $group['label']));
                                    @endphp
                                    <x-nx-card flush class="overflow-hidden">
                                        <a href="{{ route($group['routePrefix'], $board) }}" wire:navigate
                                           class="block p-4 transition-colors hover:bg-[color:var(--nx-hover)]">
                                            {{-- Karten-Kopf --}}
                                            <div class="mb-2.5 flex items-center gap-2">
                                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-[6px] bg-[color:var(--nx-accent-soft)]">
                                                    @svg($group['icon'], 'w-3.5 h-3.5 text-[color:var(--nx-muted)]')
                                                </span>
                                                <span class="text-[11px] uppercase tracking-[0.15em] text-[color:var(--nx-faint)]">{{ $typeLabel }}</span>
                                                @if($group['entryRelation'])
                                                    <span class="ml-auto shrink-0 rounded-full bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-[11px] tabular-nums text-[color:var(--nx-muted)]">{{ $entryCount }}</span>
                                                @endif
                                            </div>

                                            {{-- Board-Name --}}
                                            <h4 class="truncate font-medium text-[color:var(--nx-text)]">
                                                {{ $board->name }}
                                                @if($board->done)<span class="ml-1 text-[color:var(--nx-success)]">✓</span>@endif
                                            </h4>

                                            {{-- Kompakte Vorschau --}}
                                            <div class="mt-3 min-h-[2rem]">
                                                @if($group['key'] === 'ci')
                                                        @php($ciColors = collect([$board->primary_color, $board->secondary_color, $board->accent_color])->filter()->merge($board->colors->pluck('color'))->filter())
                                                        @if($ciColors->isNotEmpty())
                                                            <div class="flex flex-wrap items-center gap-1.5">
                                                                @foreach($ciColors->take(8) as $c)
                                                                    <span class="h-5 w-5 rounded-full ring-1 ring-[color:var(--nx-line)]" style="background-color: {{ $c }};"></span>
                                                                @endforeach
                                                            </div>
                                                            @if($board->slogan || $board->tagline)
                                                                <p class="mt-2 truncate text-xs italic text-[color:var(--nx-faint)]">&ldquo;{{ $board->slogan ?: $board->tagline }}&rdquo;</p>
                                                            @endif
                                                        @else
                                                            <span class="text-xs text-[color:var(--nx-faint)]">Keine Farben hinterlegt</span>
                                                        @endif

                                                @elseif($group['key'] === 'typography')
                                                        @forelse($board->entries->take(3) as $entry)
                                                            <div class="truncate text-sm text-[color:var(--nx-text)]">{{ $entry->font_family }}@if($entry->role)<span class="text-[color:var(--nx-faint)]"> · {{ $entry->role }}</span>@endif</div>
                                                        @empty
                                                            <span class="text-xs text-[color:var(--nx-faint)]">Keine Schriften</span>
                                                        @endforelse

                                                @elseif($group['key'] === 'logo')
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @forelse($board->variants->take(4) as $v)
                                                                <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-xs text-[color:var(--nx-muted)]">{{ Str::limit($v->name, 18) }}</span>
                                                            @empty
                                                                <span class="text-xs text-[color:var(--nx-faint)]">Keine Varianten</span>
                                                            @endforelse
                                                        </div>

                                                @elseif($group['key'] === 'moodboard')
                                                        <div class="flex gap-1.5">
                                                            @forelse($board->images->take(5) as $img)
                                                                @if($img->thumbnail_url)
                                                                    <img src="{{ $img->thumbnail_url }}" alt="{{ $img->title }}" class="h-9 w-9 rounded-[6px] object-cover ring-1 ring-[color:var(--nx-line)]">
                                                                @else
                                                                    <span class="flex h-9 w-9 items-center justify-center rounded-[6px] bg-[color:var(--nx-accent-soft)]">@svg('heroicon-o-photo', 'w-4 h-4 text-[color:var(--nx-faint)]')</span>
                                                                @endif
                                                            @empty
                                                                <span class="text-xs text-[color:var(--nx-faint)]">Keine Bilder</span>
                                                            @endforelse
                                                        </div>

                                                @elseif($group['key'] === 'persona')
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @forelse($board->personas->take(4) as $persona)
                                                                <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-xs text-[color:var(--nx-muted)]">{{ Str::limit($persona->name, 18) }}</span>
                                                            @empty
                                                                <span class="text-xs text-[color:var(--nx-faint)]">Keine Personas</span>
                                                            @endforelse
                                                        </div>

                                                @elseif($group['key'] === 'competitor')
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @forelse($board->competitors->take(4) as $c)
                                                                <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-xs text-[color:var(--nx-muted)]">{{ Str::limit($c->name, 18) }}</span>
                                                            @empty
                                                                <span class="text-xs text-[color:var(--nx-faint)]">Keine Wettbewerber</span>
                                                            @endforelse
                                                        </div>

                                                @elseif($group['key'] === 'tone-of-voice')
                                                        @if($board->dimensions->isNotEmpty())
                                                            <span class="text-xs text-[color:var(--nx-faint)]">{{ $board->dimensions->count() }} Dimensionen</span>
                                                        @elseif($board->entries->isNotEmpty())
                                                            <div class="flex flex-wrap gap-1.5">
                                                                @foreach($board->entries->take(4) as $entry)
                                                                    <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-xs text-[color:var(--nx-muted)]">{{ Str::limit($entry->name, 18) }}</span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="text-xs text-[color:var(--nx-faint)]">Keine Einträge</span>
                                                        @endif

                                                @elseif($group['key'] === 'guideline')
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @forelse($board->chapters->take(4) as $chapter)
                                                                <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-xs text-[color:var(--nx-muted)]">{{ Str::limit($chapter->title, 18) }}</span>
                                                            @empty
                                                                <span class="text-xs text-[color:var(--nx-faint)]">Keine Kapitel</span>
                                                            @endforelse
                                                        </div>

                                                @elseif(in_array($group['key'], ['social', 'kanban']))
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @forelse($board->slots->take(4) as $slot)
                                                                <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-xs text-[color:var(--nx-muted)]">{{ Str::limit($slot->name, 14) }} · {{ $slot->cards_count }}</span>
                                                            @empty
                                                                <span class="text-xs text-[color:var(--nx-faint)]">Keine Spalten</span>
                                                            @endforelse
                                                        </div>

                                                @elseif($group['key'] === 'seo')
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @forelse($board->keywords->take(4) as $keyword)
                                                                <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-xs text-[color:var(--nx-muted)]">{{ Str::limit($keyword->keyword, 18) }}</span>
                                                            @empty
                                                                <span class="text-xs text-[color:var(--nx-faint)]">Keine Keywords</span>
                                                            @endforelse
                                                        </div>

                                                @elseif($group['key'] === 'content-brief')
                                                        @if($board->content_type || $board->search_intent)
                                                            <span class="text-sm text-[color:var(--nx-text)]">{{ $board->content_type }}</span>
                                                            @if($board->search_intent)<span class="text-xs text-[color:var(--nx-faint)]"> · {{ $board->search_intent }}</span>@endif
                                                        @else
                                                            <span class="text-xs text-[color:var(--nx-faint)]">Kein Brief-Typ</span>
                                                        @endif

                                                @elseif($group['key'] === 'asset')
                                                        <div class="flex flex-wrap gap-1.5">
                                                            @forelse($board->assets->take(4) as $asset)
                                                                <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-xs text-[color:var(--nx-muted)]">{{ Str::limit($asset->name, 18) }}</span>
                                                            @empty
                                                                <span class="text-xs text-[color:var(--nx-faint)]">Keine Assets</span>
                                                            @endforelse
                                                        </div>
                                                @endif
                                            </div>
                                        </a>

                                        {{-- Footer: Meta + Export --}}
                                        <div class="flex items-center justify-between border-t border-[color:var(--nx-line)] px-4 py-2">
                                            <span class="text-[11px] text-[color:var(--nx-faint)]">
                                                @if($group['entryRelation']){{ $entryCount }} {{ $group['entryLabel'] }} · @endif{{ $board->updated_at->format('d.m.Y') }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <a href="{{ route('brands.export.download-board', ['boardType' => $group['boardType'], 'boardId' => $board->id, 'format' => 'json']) }}"
                                                   class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]" title="JSON-Export">
                                                    @svg('heroicon-o-code-bracket', 'w-3.5 h-3.5')
                                                </a>
                                                <a href="{{ route('brands.export.download-board', ['boardType' => $group['boardType'], 'boardId' => $board->id, 'format' => 'pdf']) }}"
                                                   class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]" title="PDF-Export">
                                                    @svg('heroicon-o-document', 'w-3.5 h-3.5')
                                                </a>
                                            </span>
                                        </div>
                                    </x-nx-card>
                                @endforeach
                            @endforeach
                        </div>
                    </x-nx-section>
                @endif
            @endforeach
        @elseif($accountsCount === 0)
            {{-- Leerzustand: gar keine Boards --}}
            <x-nx-card>
                <x-nx-empty icon="heroicon-o-squares-2x2">
                    Noch keine Boards – lege über „Board erstellen" das erste Board für diese Marke an.
                </x-nx-empty>
            </x-nx-card>
        @endif

        {{-- ===== Social Accounts ===== --}}
        @if($accountsCount > 0 || ($metaConnection && ($availableFacebookPages->count() > 0 || $availableInstagramAccounts->count() > 0)))
            <x-nx-section icon="heroicon-o-globe-alt" title="Social Accounts" :hint="$accountsCount . ' verknüpft'">

                {{-- Verknüpfte Accounts --}}
                @if($accountsCount > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($facebookPages as $facebookPage)
                            <x-nx-card flush>
                                <div class="flex items-center gap-3 p-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[6px] bg-[rgba(25,113,194,.1)]">
                                        @svg('heroicon-o-globe-alt', 'w-4 h-4 text-[color:var(--nx-info)]')
                                    </span>
                                    <a href="{{ route('brands.facebook-pages.show', $facebookPage) }}" wire:navigate class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $facebookPage->name }}</span>
                                        <span class="block text-xs text-[color:var(--nx-faint)]">Facebook Page</span>
                                    </a>
                                    @can('update', $brand)
                                        <button wire:click="detachFacebookPage({{ $facebookPage->id }})" wire:confirm="Facebook Page wirklich von dieser Marke trennen?"
                                                class="shrink-0 rounded p-1.5 text-[color:var(--nx-faint)] transition-colors hover:bg-[rgba(224,49,49,.08)] hover:text-[color:var(--nx-danger)]" title="Verknüpfung trennen">
                                            @svg('heroicon-o-x-mark', 'w-4 h-4')
                                        </button>
                                    @endcan
                                </div>
                            </x-nx-card>
                        @endforeach

                        @foreach($instagramAccounts as $instagramAccount)
                            <x-nx-card flush>
                                <div class="flex items-center gap-3 p-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[6px] bg-[rgba(224,49,49,.1)]">
                                        @svg('heroicon-o-camera', 'w-4 h-4 text-[color:var(--nx-danger)]')
                                    </span>
                                    <a href="{{ route('brands.instagram-accounts.show', $instagramAccount) }}" wire:navigate class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-[color:var(--nx-text)]">{{ '@' . $instagramAccount->username }}</span>
                                        <span class="block text-xs text-[color:var(--nx-faint)]">Instagram</span>
                                    </a>
                                    @can('update', $brand)
                                        <button wire:click="detachInstagramAccount({{ $instagramAccount->id }})" wire:confirm="Instagram Account wirklich von dieser Marke trennen?"
                                                class="shrink-0 rounded p-1.5 text-[color:var(--nx-faint)] transition-colors hover:bg-[rgba(224,49,49,.08)] hover:text-[color:var(--nx-danger)]" title="Verknüpfung trennen">
                                            @svg('heroicon-o-x-mark', 'w-4 h-4')
                                        </button>
                                    @endcan
                                </div>
                            </x-nx-card>
                        @endforeach
                    </div>
                @endif

                {{-- Verfügbare Accounts zum Verknüpfen (eingeklappt) --}}
                @if($metaConnection && ($availableFacebookPages->count() > 0 || $availableInstagramAccounts->count() > 0))
                    @php($availableCount = $availableFacebookPages->count() + $availableInstagramAccounts->count())
                    <div x-data="{ open: false }" class="{{ $accountsCount > 0 ? 'mt-4' : '' }}">
                        <button type="button" @click="open = !open"
                                class="flex items-center gap-1.5 text-sm text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">
                            <svg class="h-4 w-4 transition-transform" :class="open && 'rotate-90'" viewBox="0 0 20 20" fill="none">
                                <path d="M7 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Weitere Accounts verknüpfen ({{ $availableCount }})
                        </button>
                        <div x-show="open" x-cloak class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($availableFacebookPages as $facebookPage)
                                <x-nx-card flush>
                                    <div class="flex items-center gap-3 p-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[6px] bg-[rgba(25,113,194,.1)]">
                                            @svg('heroicon-o-globe-alt', 'w-4 h-4 text-[color:var(--nx-info)]')
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $facebookPage->name }}</span>
                                            <span class="block text-xs text-[color:var(--nx-faint)]">Facebook Page</span>
                                        </div>
                                        @can('update', $brand)
                                            <x-nx-button variant="secondary" size="sm" wire:click="attachFacebookPage({{ $facebookPage->id }})" class="shrink-0">
                                                @svg('heroicon-o-plus', 'w-3.5 h-3.5') Verknüpfen
                                            </x-nx-button>
                                        @endcan
                                    </div>
                                </x-nx-card>
                            @endforeach

                            @foreach($availableInstagramAccounts as $instagramAccount)
                                <x-nx-card flush>
                                    <div class="flex items-center gap-3 p-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[6px] bg-[rgba(224,49,49,.1)]">
                                            @svg('heroicon-o-camera', 'w-4 h-4 text-[color:var(--nx-danger)]')
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-[color:var(--nx-text)]">{{ '@' . $instagramAccount->username }}</span>
                                            <span class="block text-xs text-[color:var(--nx-faint)]">Instagram</span>
                                        </div>
                                        @can('update', $brand)
                                            <x-nx-button variant="secondary" size="sm" wire:click="attachInstagramAccount({{ $instagramAccount->id }})" class="shrink-0">
                                                @svg('heroicon-o-plus', 'w-3.5 h-3.5') Verknüpfen
                                            </x-nx-button>
                                        @endcan
                                    </div>
                                </x-nx-card>
                            @endforeach
                        </div>
                    </div>
                @endif
            </x-nx-section>
        @endif

    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Marken-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Mini Dashboard --}}
                <div class="bg-gradient-to-br from-[var(--ui-primary-5)] to-[var(--ui-primary-10)] rounded-xl p-4 border border-[var(--ui-primary)]/20">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-primary)] mb-4">Dashboard</h3>

                    <div class="space-y-3">
                        {{-- Boards Statistik --}}
                        @php
                            $sidebarTotalBoards = $boardGroups->sum(fn($g) => $g['boards']->count());
                        @endphp
                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-3 border border-white/50">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-squares-2x2', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm font-semibold text-[var(--ui-secondary)]">Boards</span>
                                </div>
                                <span class="text-lg font-bold text-[var(--ui-primary)]">{{ $sidebarTotalBoards }}</span>
                            </div>
                            <div class="grid grid-cols-4 gap-2 mt-2">
                                @foreach($boardGroups as $group)
                                    <div class="text-center">
                                        <div class="text-xs font-medium text-{{ $group['color'] }}-600">{{ $group['boards']->count() }}</div>
                                        <div class="text-[10px] text-[var(--ui-muted)]">{{ Str::limit($group['label'], 8, '.') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Social Accounts Statistik --}}
                        @if($facebookPages->count() > 0 || $instagramAccounts->count() > 0)
                            <div class="bg-white/80 backdrop-blur-sm rounded-lg p-3 border border-white/50">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        @svg('heroicon-o-share', 'w-4 h-4 text-[var(--ui-primary)]')
                                        <span class="text-sm font-semibold text-[var(--ui-secondary)]">Social Accounts</span>
                                    </div>
                                    <span class="text-lg font-bold text-[var(--ui-primary)]">{{ $facebookPages->count() + $instagramAccounts->count() }}</span>
                                </div>
                                <div class="flex items-center gap-3 mt-2">
                                    @if($facebookPages->count() > 0)
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                            <span class="text-xs text-[var(--ui-muted)]">{{ $facebookPages->count() }} Facebook</span>
                                        </div>
                                    @endif
                                    @if($instagramAccounts->count() > 0)
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-2 h-2 rounded-full bg-pink-500"></div>
                                            <span class="text-xs text-[var(--ui-muted)]">{{ $instagramAccounts->count() }} Instagram</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Meta Connection Status --}}
                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-3 border border-white/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-link', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm font-semibold text-[var(--ui-secondary)]">Meta Connection</span>
                                </div>
                                @if($metaConnection)
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                        <span class="text-xs font-medium text-green-600">Aktiv</span>
                                    </div>
                                @else
                                    <span class="text-xs font-medium text-[var(--ui-muted)]">Nicht verbunden</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Marken-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        @if($verortung)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Verortung</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium text-right">{{ $verortung['entity'] }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $brand->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($brand->done)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Status</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded bg-[var(--ui-success-5)] text-[var(--ui-success)]">
                                    Erledigt
                                </span>
                            </div>
                        @endif
                        @if($brand->getCompany())
                            @php
                                $company = $brand->getCompany();
                                $companyResolver = app(\Platform\Core\Contracts\CrmCompanyResolverInterface::class);
                            @endphp
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Unternehmen</span>
                                <a href="{{ $companyResolver->url($company->id) }}" class="text-sm text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $companyResolver->displayName($company->id) }}
                                </a>
                            </div>
                        @endif
                        @if($brand->getContact())
                            @php
                                $contact = $brand->getContact();
                                $contactResolver = app(\Platform\Core\Contracts\CrmContactResolverInterface::class);
                            @endphp
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Kontaktperson</span>
                                <a href="{{ $contactResolver->url($contact->id) }}" class="text-sm text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $contactResolver->displayName($contact->id) }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Letzte Aktivitäten</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)] hover:bg-[var(--ui-muted)] transition-colors">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-[var(--ui-secondary)] leading-snug">
                                        {{ $activity['title'] ?? 'Aktivität' }}
                                    </div>
                                </div>
                                @if(($activity['type'] ?? null) === 'system')
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 text-xs text-[var(--ui-muted)]">
                                            @svg('heroicon-o-cog', 'w-3 h-3')
                                            System
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-[var(--ui-muted)]">
                                @svg('heroicon-o-clock', 'w-3 h-3')
                                <span>{{ $activity['time'] ?? '' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                                @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivitäten</p>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">Änderungen werden hier angezeigt</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <livewire:brands.brand-settings-modal/>
    <livewire:brands.facebook-page-modal/>
</x-ui-page>
