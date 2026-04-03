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

    <x-ui-page-container spacing="space-y-6">
        {{-- Boards Section --}}
        <div>
            @if($boardGroups->count() > 0 || $facebookPages->count() > 0 || $instagramAccounts->count() > 0)
                <div class="space-y-6">
                    {{-- Alle Boards als 2-Spalten Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        @foreach($boardGroups as $group)
                            @foreach($group['boards'] as $board)
                                @php
                                    $countAttr = $group['entryRelation'] ? $group['entryRelation'] . '_count' : null;
                                    $entryCount = $countAttr && isset($board->$countAttr) ? $board->$countAttr : ($group['entryRelation'] ? $board->{$group['entryRelation']}->count() : 0);
                                @endphp
                                <a href="{{ route($group['routePrefix'], $board) }}" wire:navigate
                                   class="group relative flex flex-col bg-white rounded-2xl border border-{{ $group['color'] }}-100 hover:border-{{ $group['color'] }}-300 shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 overflow-hidden">

                                    {{-- Farbiger Top-Gradient --}}
                                    <div class="h-1.5 bg-gradient-to-r from-{{ $group['color'] }}-400 via-{{ $group['color'] }}-300 to-{{ $group['color'] }}-200"></div>

                                    {{-- Card Body --}}
                                    <div class="flex-1 p-5">
                                        {{-- Header: Icon + Name + Badge --}}
                                        <div class="flex items-start gap-3.5 mb-4">
                                            <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-gradient-to-br from-{{ $group['color'] }}-50 to-{{ $group['color'] }}-100/80 flex items-center justify-center shadow-sm ring-1 ring-{{ $group['color'] }}-200/50">
                                                @svg($group['icon'], 'w-5.5 h-5.5 text-' . $group['color'] . '-600')
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-semibold text-base text-[var(--ui-secondary)] group-hover:text-{{ $group['color'] }}-700 transition-colors leading-tight truncate">{{ $board->name }}</h4>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-[11px] font-medium uppercase tracking-wider text-{{ $group['color'] }}-500">{{ $group['label'] }}</span>
                                                    @if($board->done)
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-green-50 text-green-600 text-[10px] font-semibold ring-1 ring-green-200/50">
                                                            @svg('heroicon-s-check-circle', 'w-3 h-3')
                                                            Erledigt
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if($board->description)
                                            <p class="text-sm text-[var(--ui-muted)] mb-4 line-clamp-2 leading-relaxed">{{ $board->description }}</p>
                                        @endif

                                        {{-- Preview --}}
                                        <div class="min-h-[2.5rem]">
                                            @if($group['key'] === 'ci')
                                                <div class="space-y-2.5">
                                                    <div class="flex items-center">
                                                        @if($board->primary_color)
                                                            <div class="w-8 h-8 rounded-full border-2 border-white shadow ring-1 ring-black/5" style="background-color: {{ $board->primary_color }};"></div>
                                                        @endif
                                                        @if($board->secondary_color)
                                                            <div class="w-8 h-8 rounded-full border-2 border-white shadow ring-1 ring-black/5 -ml-2" style="background-color: {{ $board->secondary_color }};"></div>
                                                        @endif
                                                        @if($board->accent_color)
                                                            <div class="w-8 h-8 rounded-full border-2 border-white shadow ring-1 ring-black/5 -ml-2" style="background-color: {{ $board->accent_color }};"></div>
                                                        @endif
                                                        @foreach($board->colors->take(4) as $color)
                                                            <div class="w-8 h-8 rounded-full border-2 border-white shadow ring-1 ring-black/5 -ml-2" style="background-color: {{ $color->color }};" title="{{ $color->title }}"></div>
                                                        @endforeach
                                                    </div>
                                                    @if($board->slogan)
                                                        <p class="text-sm text-[var(--ui-muted)] italic leading-snug">&ldquo;{{ Str::limit($board->slogan, 60) }}&rdquo;</p>
                                                    @elseif($board->tagline)
                                                        <p class="text-sm text-[var(--ui-muted)] italic leading-snug">&ldquo;{{ Str::limit($board->tagline, 60) }}&rdquo;</p>
                                                    @endif
                                                </div>
                                            @elseif($group['key'] === 'social' || $group['key'] === 'kanban')
                                                <div class="flex flex-wrap gap-2">
                                                    @forelse($board->slots->take(4) as $slot)
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-{{ $group['color'] }}-50/80 text-{{ $group['color'] }}-700 text-xs font-medium ring-1 ring-{{ $group['color'] }}-100">
                                                            {{ Str::limit($slot->name, 14) }}
                                                            <span class="bg-{{ $group['color'] }}-200/60 text-{{ $group['color'] }}-800 rounded-full px-1.5 py-px text-[10px] font-bold">{{ $slot->cards_count }}</span>
                                                        </span>
                                                    @empty
                                                        <span class="text-sm text-[var(--ui-muted)]">Noch keine Spalten</span>
                                                    @endforelse
                                                    @if($board->slots->count() > 4)
                                                        <span class="self-center text-xs text-[var(--ui-muted)] font-medium">+{{ $board->slots->count() - 4 }}</span>
                                                    @endif
                                                </div>
                                            @elseif($group['key'] === 'typography')
                                                <div class="space-y-1.5">
                                                    @forelse($board->entries->take(3) as $entry)
                                                        <div class="flex items-baseline gap-2">
                                                            <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $entry->font_family }}</span>
                                                            @if($entry->role)
                                                                <span class="text-xs text-{{ $group['color'] }}-400 font-medium">{{ $entry->role }}</span>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <span class="text-sm text-[var(--ui-muted)]">Noch keine Schriften</span>
                                                    @endforelse
                                                </div>
                                            @elseif($group['key'] === 'logo')
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-9 h-9 rounded-lg bg-{{ $group['color'] }}-50 flex items-center justify-center ring-1 ring-{{ $group['color'] }}-100">
                                                        @svg('heroicon-o-photo', 'w-4.5 h-4.5 text-' . $group['color'] . '-500')
                                                    </div>
                                                    <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $board->variants_count }} {{ $board->variants_count === 1 ? 'Variante' : 'Varianten' }}</span>
                                                </div>
                                            @elseif($group['key'] === 'tone-of-voice')
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-9 h-9 rounded-lg bg-{{ $group['color'] }}-50 flex items-center justify-center ring-1 ring-{{ $group['color'] }}-100">
                                                        @svg('heroicon-o-megaphone', 'w-4.5 h-4.5 text-' . $group['color'] . '-500')
                                                    </div>
                                                    <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $board->entries_count }} {{ $board->entries_count === 1 ? 'Eintrag' : 'Einträge' }}</span>
                                                </div>
                                            @elseif($group['key'] === 'persona')
                                                <div class="flex flex-wrap gap-2">
                                                    @forelse($board->personas->take(4) as $persona)
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-teal-50/80 text-teal-700 text-xs font-medium ring-1 ring-teal-100">{{ Str::limit($persona->name, 18) }}</span>
                                                    @empty
                                                        <span class="text-sm text-[var(--ui-muted)]">Noch keine Personas</span>
                                                    @endforelse
                                                    @if($entryCount > 4)
                                                        <span class="self-center text-xs text-[var(--ui-muted)] font-medium">+{{ $entryCount - 4 }}</span>
                                                    @endif
                                                </div>
                                            @elseif($group['key'] === 'competitor')
                                                <div class="flex flex-wrap gap-2">
                                                    @forelse($board->competitors->take(4) as $competitor)
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-orange-50/80 text-orange-700 text-xs font-medium ring-1 ring-orange-100">{{ Str::limit($competitor->name, 18) }}</span>
                                                    @empty
                                                        <span class="text-sm text-[var(--ui-muted)]">Noch keine Wettbewerber</span>
                                                    @endforelse
                                                    @if($entryCount > 4)
                                                        <span class="self-center text-xs text-[var(--ui-muted)] font-medium">+{{ $entryCount - 4 }}</span>
                                                    @endif
                                                </div>
                                            @elseif($group['key'] === 'guideline')
                                                <div class="space-y-1.5">
                                                    @forelse($board->chapters->take(3) as $chapter)
                                                        <div class="flex items-center gap-2 text-sm text-[var(--ui-secondary)]">
                                                            <div class="w-1.5 h-1.5 rounded-full bg-{{ $group['color'] }}-400"></div>
                                                            {{ Str::limit($chapter->title, 30) }}
                                                        </div>
                                                    @empty
                                                        <span class="text-sm text-[var(--ui-muted)]">Noch keine Kapitel</span>
                                                    @endforelse
                                                </div>
                                            @elseif($group['key'] === 'moodboard')
                                                <div class="flex items-center gap-2">
                                                    @forelse($board->images->take(4) as $image)
                                                        @if($image->thumbnail_url)
                                                            <div class="w-12 h-12 rounded-lg bg-[var(--ui-muted-5)] overflow-hidden shadow-sm ring-1 ring-black/5">
                                                                <img src="{{ $image->thumbnail_url }}" alt="{{ $image->title }}" class="w-full h-full object-cover">
                                                            </div>
                                                        @else
                                                            <div class="w-12 h-12 rounded-lg bg-[var(--ui-muted-5)] flex items-center justify-center ring-1 ring-black/5">
                                                                @svg('heroicon-o-photo', 'w-5 h-5 text-[var(--ui-muted)]')
                                                            </div>
                                                        @endif
                                                    @empty
                                                        <span class="text-sm text-[var(--ui-muted)]">Noch keine Bilder</span>
                                                    @endforelse
                                                    @if($entryCount > 4)
                                                        <span class="text-xs text-[var(--ui-muted)] font-medium">+{{ $entryCount - 4 }}</span>
                                                    @endif
                                                </div>
                                            @elseif($group['key'] === 'asset')
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-9 h-9 rounded-lg bg-{{ $group['color'] }}-50 flex items-center justify-center ring-1 ring-{{ $group['color'] }}-100">
                                                        @svg('heroicon-o-folder-open', 'w-4.5 h-4.5 text-' . $group['color'] . '-500')
                                                    </div>
                                                    <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $board->assets_count }} {{ $board->assets_count === 1 ? 'Asset' : 'Assets' }}</span>
                                                </div>
                                            @elseif($group['key'] === 'seo')
                                                <div class="flex flex-wrap gap-2">
                                                    @forelse($board->keywords->take(4) as $keyword)
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-lime-50/80 text-lime-700 text-xs font-medium ring-1 ring-lime-100">{{ Str::limit($keyword->keyword, 20) }}</span>
                                                    @empty
                                                        <span class="text-sm text-[var(--ui-muted)]">Noch keine Keywords</span>
                                                    @endforelse
                                                    @if($entryCount > 4)
                                                        <span class="self-center text-xs text-[var(--ui-muted)] font-medium">+{{ $entryCount - 4 }}</span>
                                                    @endif
                                                </div>
                                            @elseif($group['key'] === 'content-brief')
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-9 h-9 rounded-lg bg-{{ $group['color'] }}-50 flex items-center justify-center ring-1 ring-{{ $group['color'] }}-100">
                                                        @svg('heroicon-o-document-text', 'w-4.5 h-4.5 text-' . $group['color'] . '-500')
                                                    </div>
                                                    <span class="text-sm text-[var(--ui-secondary)] font-medium">Content Brief</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Footer --}}
                                    <div class="px-5 py-3 border-t border-{{ $group['color'] }}-50 bg-gradient-to-r from-{{ $group['color'] }}-50/30 to-transparent flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-xs text-[var(--ui-muted)]">
                                            @if($group['entryRelation'])
                                                <span class="font-semibold text-[var(--ui-secondary)]">{{ $entryCount }}</span>
                                                <span>{{ $group['entryLabel'] }}</span>
                                                <span class="text-[var(--ui-border)]">&middot;</span>
                                            @endif
                                            <span>{{ $board->updated_at->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity" onclick="event.preventDefault(); event.stopPropagation();">
                                            <a href="{{ route('brands.export.download-board', ['boardType' => $group['boardType'], 'boardId' => $board->id, 'format' => 'json']) }}" class="p-1.5 text-[var(--ui-muted)] hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="JSON exportieren">
                                                @svg('heroicon-o-code-bracket', 'w-3.5 h-3.5')
                                            </a>
                                            <a href="{{ route('brands.export.download-board', ['boardType' => $group['boardType'], 'boardId' => $board->id, 'format' => 'pdf']) }}" class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="PDF exportieren">
                                                @svg('heroicon-o-document', 'w-3.5 h-3.5')
                                            </a>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        @endforeach
                    </div>

                    {{-- Social Accounts Gruppe (Facebook Pages & Instagram) --}}
                    @if($facebookPages->count() > 0 || $instagramAccounts->count() > 0)
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-gradient-to-br from-blue-50 to-pink-50">
                                    @svg('heroicon-o-globe-alt', 'w-4 h-4 text-blue-600')
                                </div>
                                <h3 class="text-sm font-semibold text-[var(--ui-secondary)] uppercase tracking-wide">Social Accounts</h3>
                                <span class="text-xs text-[var(--ui-muted)]">({{ $facebookPages->count() + $instagramAccounts->count() }})</span>
                            </div>

                            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-[var(--ui-border)]/40">
                                            <th class="text-left text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wider px-4 py-2.5">Name</th>
                                            <th class="text-left text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wider px-4 py-2.5 hidden sm:table-cell">Typ</th>
                                            <th class="text-right text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wider px-4 py-2.5 hidden lg:table-cell">Verknüpft seit</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[var(--ui-border)]/30">
                                        @foreach($facebookPages as $facebookPage)
                                            <tr class="group hover:bg-blue-50/30 transition-colors">
                                                <td class="px-4 py-3">
                                                    <a href="{{ route('brands.facebook-pages.show', $facebookPage) }}" class="block">
                                                        <div class="font-medium text-sm text-[var(--ui-secondary)] group-hover:text-blue-600 transition-colors">{{ $facebookPage->name }}</div>
                                                        @if($facebookPage->description)
                                                            <div class="text-xs text-[var(--ui-muted)] mt-0.5 line-clamp-1">{{ $facebookPage->description }}</div>
                                                        @endif
                                                    </a>
                                                </td>
                                                <td class="px-4 py-3 hidden sm:table-cell">
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">
                                                        @svg('heroicon-o-globe-alt', 'w-3.5 h-3.5')
                                                        Facebook Page
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right hidden lg:table-cell">
                                                    <span class="text-xs text-[var(--ui-muted)]">{{ $facebookPage->created_at->format('d.m.Y') }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        @foreach($instagramAccounts as $instagramAccount)
                                            <tr class="group hover:bg-pink-50/30 transition-colors">
                                                <td class="px-4 py-3">
                                                    <a href="{{ route('brands.instagram-accounts.show', $instagramAccount) }}" class="block">
                                                        <div class="font-medium text-sm text-[var(--ui-secondary)] group-hover:text-pink-600 transition-colors">{{ '@' . $instagramAccount->username }}</div>
                                                        @if($instagramAccount->description)
                                                            <div class="text-xs text-[var(--ui-muted)] mt-0.5 line-clamp-1">{{ $instagramAccount->description }}</div>
                                                        @endif
                                                    </a>
                                                </td>
                                                <td class="px-4 py-3 hidden sm:table-cell">
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-pink-50 text-pink-700 text-xs font-medium">
                                                        @svg('heroicon-o-camera', 'w-3.5 h-3.5')
                                                        Instagram
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right hidden lg:table-cell">
                                                    <span class="text-xs text-[var(--ui-muted)]">{{ $instagramAccount->created_at->format('d.m.Y') }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-[var(--ui-primary-5)] to-[var(--ui-primary-10)] mb-4">
                        @svg('heroicon-o-squares-2x2', 'w-8 h-8 text-[var(--ui-primary)]')
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Boards</h3>
                    <p class="text-sm text-[var(--ui-muted)] mb-6">Erstelle dein erstes Board für diese Marke.</p>
                    @can('update', $brand)
                        <x-ui-button variant="primary" size="sm" wire:click="createContentBriefBoard">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Board erstellen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
            @endif
        </div>

        {{-- Verknüpfte Social Accounts als Liste --}}
        @if($facebookPages->count() > 0 || $instagramAccounts->count() > 0)
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Verknüpfte Social Accounts</h2>
                </div>
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                    <div class="divide-y divide-[var(--ui-border)]/40">
                        @foreach($facebookPages as $facebookPage)
                            <div class="p-4 hover:bg-[var(--ui-muted-5)] transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50">
                                            @svg('heroicon-o-globe-alt', 'w-5 h-5 text-blue-600')
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('brands.facebook-pages.show', $facebookPage) }}" class="block">
                                                <h4 class="text-sm font-semibold text-[var(--ui-secondary)] hover:text-blue-600 transition-colors truncate">{{ $facebookPage->name }}</h4>
                                            </a>
                                            @if($facebookPage->description)
                                                <p class="text-xs text-[var(--ui-muted)] mt-0.5 line-clamp-1">{{ $facebookPage->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @can('update', $brand)
                                        <button
                                            wire:click="detachFacebookPage({{ $facebookPage->id }})"
                                            wire:confirm="Facebook Page wirklich von dieser Marke trennen?"
                                            class="flex-shrink-0 ml-3 p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-error)] hover:bg-red-50 rounded transition-colors"
                                            title="Verknüpfung trennen"
                                        >
                                            @svg('heroicon-o-x-mark', 'w-4 h-4')
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        @endforeach

                        @foreach($instagramAccounts as $instagramAccount)
                            <div class="p-4 hover:bg-[var(--ui-muted-5)] transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg bg-pink-50">
                                            @svg('heroicon-o-camera', 'w-5 h-5 text-pink-600')
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('brands.instagram-accounts.show', $instagramAccount) }}" class="block">
                                                <h4 class="text-sm font-semibold text-[var(--ui-secondary)] hover:text-pink-600 transition-colors truncate">{{ '@' . $instagramAccount->username }}</h4>
                                            </a>
                                            @if($instagramAccount->description)
                                                <p class="text-xs text-[var(--ui-muted)] mt-0.5 line-clamp-1">{{ $instagramAccount->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @can('update', $brand)
                                        <button
                                            wire:click="detachInstagramAccount({{ $instagramAccount->id }})"
                                            wire:confirm="Instagram Account wirklich von dieser Marke trennen?"
                                            class="flex-shrink-0 ml-3 p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-error)] hover:bg-red-50 rounded transition-colors"
                                            title="Verknüpfung trennen"
                                        >
                                            @svg('heroicon-o-x-mark', 'w-4 h-4')
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Verfügbare Accounts zum Verknüpfen --}}
        @if($metaConnection && ($availableFacebookPages->count() > 0 || $availableInstagramAccounts->count() > 0))
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Accounts verknüpfen</h2>
                </div>
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                    <div class="divide-y divide-[var(--ui-border)]/40">
                        @foreach($availableFacebookPages as $facebookPage)
                            <div class="p-4 hover:bg-[var(--ui-muted-5)] transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50">
                                            @svg('heroicon-o-globe-alt', 'w-5 h-5 text-blue-600')
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-semibold text-[var(--ui-secondary)] truncate">{{ $facebookPage->name }}</h4>
                                            @if($facebookPage->description)
                                                <p class="text-xs text-[var(--ui-muted)] mt-0.5 line-clamp-1">{{ $facebookPage->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @can('update', $brand)
                                        <x-ui-button
                                            variant="primary"
                                            size="sm"
                                            wire:click="attachFacebookPage({{ $facebookPage->id }})"
                                            class="ml-3 flex-shrink-0"
                                        >
                                            <span class="inline-flex items-center gap-1.5">
                                                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                                <span>Verknüpfen</span>
                                            </span>
                                        </x-ui-button>
                                    @endcan
                                </div>
                            </div>
                        @endforeach

                        @foreach($availableInstagramAccounts as $instagramAccount)
                            <div class="p-4 hover:bg-[var(--ui-muted-5)] transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg bg-pink-50">
                                            @svg('heroicon-o-camera', 'w-5 h-5 text-pink-600')
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-semibold text-[var(--ui-secondary)] truncate">{{ '@' . $instagramAccount->username }}</h4>
                                            @if($instagramAccount->description)
                                                <p class="text-xs text-[var(--ui-muted)] mt-0.5 line-clamp-1">{{ $instagramAccount->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @can('update', $brand)
                                        <x-ui-button
                                            variant="primary"
                                            size="sm"
                                            wire:click="attachInstagramAccount({{ $instagramAccount->id }})"
                                            class="ml-3 flex-shrink-0"
                                        >
                                            <span class="inline-flex items-center gap-1.5">
                                                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                                <span>Verknüpfen</span>
                                            </span>
                                        </x-ui-button>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
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
                            $totalBoards = $boardGroups->sum(fn($g) => $g['boards']->count());
                        @endphp
                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-3 border border-white/50">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-squares-2x2', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm font-semibold text-[var(--ui-secondary)]">Boards</span>
                                </div>
                                <span class="text-lg font-bold text-[var(--ui-primary)]">{{ $totalBoards }}</span>
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
