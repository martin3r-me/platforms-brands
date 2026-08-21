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
                    <x-nx-button variant="ghost" size="sm" @click="$dispatch('open-modal-brand-settings', { brandId: {{ $brand->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                        <span>Einstellungen</span>
                    </x-nx-button>
                @endcan
                <a href="{{ route('brands.export.show', $brand) }}" wire:navigate>
                    <x-nx-button variant="ghost" size="sm">
                        @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                        <span>Export</span>
                    </x-nx-button>
                </a>
            </x-slot>

            {{-- Right: Board erstellen Dropdown --}}
            @can('update', $brand)
                <div class="relative" x-data="{ open: false }">
                    <x-nx-button variant="primary" size="sm" @click="open = !open">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Board erstellen</span>
                        @svg('heroicon-o-chevron-down', 'w-4 h-4')
                    </x-nx-button>

                    <div
                        x-show="open"
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-[var(--nx-line)]/60 z-10 overflow-hidden"
                        style="display: none;"
                    >
                        <div class="py-1 max-h-96 overflow-y-auto">
                            <button wire:click="createBoard('social')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-purple-50">@svg('heroicon-o-share', 'w-4 h-4 text-purple-600')</div>
                                <div><div class="font-medium">Social Board</div><div class="text-xs text-[var(--nx-faint)]">Für Social Media</div></div>
                            </button>
                            <button wire:click="createBoard('ci')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-amber-50">@svg('heroicon-o-paint-brush', 'w-4 h-4 text-amber-600')</div>
                                <div><div class="font-medium">CI Board</div><div class="text-xs text-[var(--nx-faint)]">Für Corporate Identity</div></div>
                            </button>
                            <button wire:click="createBoard('kanban')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-indigo-50">@svg('heroicon-o-view-columns', 'w-4 h-4 text-indigo-600')</div>
                                <div><div class="font-medium">Kanban Board</div><div class="text-xs text-[var(--nx-faint)]">Für Aufgabenverwaltung</div></div>
                            </button>
                            <button wire:click="createBoard('typography')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-rose-50">@svg('heroicon-o-language', 'w-4 h-4 text-rose-600')</div>
                                <div><div class="font-medium">Typografie Board</div><div class="text-xs text-[var(--nx-faint)]">Schriften & Hierarchien</div></div>
                            </button>
                            <button wire:click="createBoard('logo')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-emerald-50">@svg('heroicon-o-photo', 'w-4 h-4 text-emerald-600')</div>
                                <div><div class="font-medium">Logo Board</div><div class="text-xs text-[var(--nx-faint)]">Logo-Varianten verwalten</div></div>
                            </button>
                            <button wire:click="createBoard('tone-of-voice')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-violet-50">@svg('heroicon-o-megaphone', 'w-4 h-4 text-violet-600')</div>
                                <div><div class="font-medium">Tone of Voice Board</div><div class="text-xs text-[var(--nx-faint)]">Markenstimme & Messaging</div></div>
                            </button>
                            <button wire:click="createBoard('persona')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-teal-50">@svg('heroicon-o-user-group', 'w-4 h-4 text-teal-600')</div>
                                <div><div class="font-medium">Persona Board</div><div class="text-xs text-[var(--nx-faint)]">Zielgruppen & Personas</div></div>
                            </button>
                            <button wire:click="createBoard('competitor')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-orange-50">@svg('heroicon-o-scale', 'w-4 h-4 text-orange-600')</div>
                                <div><div class="font-medium">Wettbewerber Board</div><div class="text-xs text-[var(--nx-faint)]">Wettbewerber-Analyse</div></div>
                            </button>
                            <button wire:click="createBoard('reference')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-sky-50">@svg('heroicon-o-link', 'w-4 h-4 text-sky-600')</div>
                                <div><div class="font-medium">Referenzen Board</div><div class="text-xs text-[var(--nx-faint)]">Website-Benchmarks & Vorbilder</div></div>
                            </button>
                            <button wire:click="createBoard('guideline')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-cyan-50">@svg('heroicon-o-book-open', 'w-4 h-4 text-cyan-600')</div>
                                <div><div class="font-medium">Guidelines Board</div><div class="text-xs text-[var(--nx-faint)]">Markenregeln & Dos/Don'ts</div></div>
                            </button>
                            <button wire:click="createBoard('moodboard')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-rose-50">@svg('heroicon-o-photo', 'w-4 h-4 text-rose-600')</div>
                                <div><div class="font-medium">Moodboard</div><div class="text-xs text-[var(--nx-faint)]">Bildsprache & Stilrichtung</div></div>
                            </button>
                            <button wire:click="createBoard('asset')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-sky-50">@svg('heroicon-o-folder-open', 'w-4 h-4 text-sky-600')</div>
                                <div><div class="font-medium">Asset Board</div><div class="text-xs text-[var(--nx-faint)]">Templates & Brand Assets</div></div>
                            </button>
                            <button wire:click="createBoard('seo')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-lime-50">@svg('heroicon-o-magnifying-glass', 'w-4 h-4 text-lime-600')</div>
                                <div><div class="font-medium">SEO Board</div><div class="text-xs text-[var(--nx-faint)]">Keyword-Recherche</div></div>
                            </button>
                            <button wire:click="createBoard('content-brief')" @click="open = false" class="w-full text-left px-4 py-2.5 text-sm text-[var(--nx-text)] hover:bg-[var(--nx-hover)] transition-colors flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 rounded-md bg-fuchsia-50">@svg('heroicon-o-document-magnifying-glass', 'w-4 h-4 text-fuchsia-600')</div>
                                <div><div class="font-medium">Content Brief Board</div><div class="text-xs text-[var(--nx-faint)]">Content-Planung</div></div>
                            </button>
                        </div>
                    </div>
                </div>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-0" width="contained">

        @php
            $ci = $ciBoards->first();
            $primary = $ci?->primary_color;
            $slogan = $ci?->slogan ?: $ci?->tagline;
            $coverColors = collect([$ci?->primary_color, $ci?->accent_color, $ci?->secondary_color])->filter();
            if ($ci) { $coverColors = $coverColors->merge($ci->colors->pluck('color')); }
            $coverColors = $coverColors->filter()->values();

            $totalBoards = $ciBoards->count() + $socialBoards->count() + $kanbanBoards->count()
                + $typographyBoards->count() + $logoBoards->count() + $toneOfVoiceBoards->count()
                + $personaBoards->count() + $competitorBoards->count() + $referenceBoards->count() + $guidelineBoards->count()
                + $moodboardBoards->count() + $seoBoards->count() + $assetBoards->count() + $contentBriefBoards->count();
            $typeCount = collect([$ciBoards, $socialBoards, $kanbanBoards, $typographyBoards, $logoBoards,
                $toneOfVoiceBoards, $personaBoards, $competitorBoards, $referenceBoards, $guidelineBoards, $moodboardBoards,
                $seoBoards, $assetBoards, $contentBriefBoards])->filter->isNotEmpty()->count();

            $hasIdentity = $ciBoards->isNotEmpty() || $typographyBoards->isNotEmpty() || $logoBoards->isNotEmpty() || $moodboardBoards->isNotEmpty();
            $hasStrategie = $personaBoards->isNotEmpty() || $competitorBoards->isNotEmpty() || $referenceBoards->isNotEmpty() || $toneOfVoiceBoards->isNotEmpty() || $guidelineBoards->isNotEmpty();
            $hasOps = $socialBoards->isNotEmpty() || $kanbanBoards->isNotEmpty() || $seoBoards->isNotEmpty() || $contentBriefBoards->isNotEmpty() || $assetBoards->isNotEmpty();

            $accountsCount = $facebookPages->count() + $instagramAccounts->count();

            // Design-Links normalisieren (klickbar auch ohne Protokoll)
            $normalizeLink = fn($u) => $u ? (preg_match('#^https?://#i', $u) ? $u : 'https://' . ltrim($u, '/')) : null;
            $wireframeUrl = $normalizeLink($brand->wireframe_url);
            $mockupUrl = $normalizeLink($brand->mockup_url);

            // Font-Fallback-Stacks für echtes Rendering der Katalog-Schriften
            $fontFallbacks = config('brands.font_fallbacks', []);
            $catalogByFamily = collect(config('brands.fonts', []))->keyBy('family');
        @endphp

        {{-- Self-hosted Katalog-Fonts (lädt nur, was gerendert wird) --}}
        @include('brands::partials.fonts')

        {{-- ══════════ COVER (aus CI abgeleitet) ══════════ --}}
        <div class="flex h-24 md:h-28 overflow-hidden rounded-t-[12px]" aria-hidden="true">
            @if($coverColors->isNotEmpty())
                @foreach($coverColors->take(6) as $c)
                    <span class="flex-1" style="background-color: {{ $c }};"></span>
                @endforeach
            @else
                <span class="flex-1 bg-[color:var(--nx-accent-soft)]"></span>
            @endif
        </div>

        {{-- ══════════ TITEL (Signet überlappt Cover, Titel darunter) ══════════ --}}
        <div class="px-1">
            @if($primary)
                <div class="-mt-10 flex h-[72px] w-[72px] items-center justify-center rounded-[16px] text-2xl font-bold text-white ring-4 ring-[color:var(--nx-surface)]" style="background-color: {{ $primary }};">
                    {{ mb_strtoupper(mb_substr($brand->name, 0, 1)) }}
                </div>
            @else
                <div class="-mt-10 flex h-[72px] w-[72px] items-center justify-center rounded-[16px] bg-[color:var(--nx-accent-soft)] text-2xl font-bold text-[color:var(--nx-muted)] ring-4 ring-[color:var(--nx-surface)]">
                    {{ mb_strtoupper(mb_substr($brand->name, 0, 1)) }}
                </div>
            @endif
            <h1 class="mt-3.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-2xl md:text-[30px] font-bold leading-tight tracking-tight text-[color:var(--nx-text)]">
                {{ $brand->name }}
                @if($brand->done)
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-[color:var(--nx-muted)]"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>Archiviert</span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-[color:var(--nx-success)]"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>Aktiv</span>
                @endif
            </h1>
            @if($slogan)
                <p class="mt-1.5 text-[15px] italic text-[color:var(--nx-muted)]">&ldquo;{{ Str::limit($slogan, 120) }}&rdquo;</p>
            @endif
        </div>

        @if($brand->description)
            <p class="mt-3.5 max-w-[66ch] px-1 text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $brand->description }}</p>
        @endif

        {{-- ══════════ PROPERTIES ══════════ --}}
        <div class="mt-6 grid grid-cols-1 gap-x-10 gap-y-0.5 md:grid-cols-2">
            {{-- Verortung --}}
            <div class="grid grid-cols-[150px_1fr] items-center rounded-[6px] px-2 py-1.5 hover:bg-[color:var(--nx-hover)]">
                <span class="flex items-center gap-2 text-[13.5px] text-[color:var(--nx-faint)]">@svg('heroicon-o-building-office-2', 'w-[15px] h-[15px]') Verortung</span>
                <span class="text-[13.5px]">
                    @if($verortung)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[rgba(25,113,194,.1)] px-2 py-0.5 text-[12.5px] text-[color:var(--nx-info)]">{{ $verortung['entity'] }}</span>
                        @if($verortung['type'])<span class="text-[color:var(--nx-faint)]"> · {{ $verortung['type'] }}</span>@endif
                    @else
                        <span class="text-[color:var(--nx-faint)]">Unverknüpft</span>
                    @endif
                </span>
            </div>
            {{-- Engagement --}}
            @if($verortung && !empty($verortung['via']))
                <div class="grid grid-cols-[150px_1fr] items-center rounded-[6px] px-2 py-1.5 hover:bg-[color:var(--nx-hover)]">
                    <span class="flex items-center gap-2 text-[13.5px] text-[color:var(--nx-faint)]">@svg('heroicon-o-link', 'w-[15px] h-[15px]') Engagement</span>
                    <span class="text-[13.5px] text-[color:var(--nx-text)]">{{ $verortung['via'] }}</span>
                </div>
            @endif
            {{-- Meta Connection --}}
            <div class="grid grid-cols-[150px_1fr] items-center rounded-[6px] px-2 py-1.5 hover:bg-[color:var(--nx-hover)]">
                <span class="flex items-center gap-2 text-[13.5px] text-[color:var(--nx-faint)]">@svg('heroicon-o-bolt', 'w-[15px] h-[15px]') Meta Connection</span>
                @if($metaConnection)
                    <span class="text-[13.5px] text-[color:var(--nx-success)]">Aktiv</span>
                @else
                    <span class="text-[13.5px] text-[color:var(--nx-faint)]">nicht verbunden</span>
                @endif
            </div>
            {{-- Boards --}}
            <div class="grid grid-cols-[150px_1fr] items-center rounded-[6px] px-2 py-1.5 hover:bg-[color:var(--nx-hover)]">
                <span class="flex items-center gap-2 text-[13.5px] text-[color:var(--nx-faint)]">@svg('heroicon-o-squares-2x2', 'w-[15px] h-[15px]') Boards</span>
                <span class="text-[13.5px] tabular-nums text-[color:var(--nx-text)]">{{ $totalBoards }} <span class="text-[color:var(--nx-faint)]">· {{ $typeCount }} Typen</span></span>
            </div>
            {{-- Accounts --}}
            <div class="grid grid-cols-[150px_1fr] items-center rounded-[6px] px-2 py-1.5 hover:bg-[color:var(--nx-hover)]">
                <span class="flex items-center gap-2 text-[13.5px] text-[color:var(--nx-faint)]">@svg('heroicon-o-share', 'w-[15px] h-[15px]') Social Accounts</span>
                <span class="text-[13.5px] tabular-nums {{ $accountsCount ? 'text-[color:var(--nx-text)]' : 'text-[color:var(--nx-faint)]' }}">{{ $accountsCount }}</span>
            </div>
            {{-- Erstellt --}}
            <div class="grid grid-cols-[150px_1fr] items-center rounded-[6px] px-2 py-1.5 hover:bg-[color:var(--nx-hover)]">
                <span class="flex items-center gap-2 text-[13.5px] text-[color:var(--nx-faint)]">@svg('heroicon-o-calendar', 'w-[15px] h-[15px]') Erstellt</span>
                <span class="text-[13.5px] text-[color:var(--nx-text)]">{{ $brand->created_at->format('d.m.Y') }}</span>
            </div>
            {{-- Wireframe (immer sichtbar) --}}
            <div class="grid grid-cols-[150px_1fr] items-center rounded-[6px] px-2 py-1.5 hover:bg-[color:var(--nx-hover)]">
                <span class="flex items-center gap-2 text-[13.5px] text-[color:var(--nx-faint)]">@svg('heroicon-o-rectangle-group', 'w-[15px] h-[15px]') Wireframe</span>
                @if($wireframeUrl)
                    <a href="{{ $wireframeUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[13.5px] text-[color:var(--nx-info)] hover:underline">Öffnen @svg('heroicon-o-arrow-top-right-on-square', 'w-3.5 h-3.5')</a>
                @else
                    @can('update', $brand)
                        <button type="button" @click="$dispatch('open-modal-brand-settings', { brandId: {{ $brand->id }} })" class="inline-flex items-center gap-1 text-[13.5px] text-[color:var(--nx-faint)] transition-colors hover:text-[color:var(--nx-text)]">@svg('heroicon-o-plus', 'w-3.5 h-3.5') hinterlegen</button>
                    @else
                        <span class="text-[13.5px] text-[color:var(--nx-faint)]">–</span>
                    @endcan
                @endif
            </div>
            {{-- Mockup (immer sichtbar) --}}
            <div class="grid grid-cols-[150px_1fr] items-center rounded-[6px] px-2 py-1.5 hover:bg-[color:var(--nx-hover)]">
                <span class="flex items-center gap-2 text-[13.5px] text-[color:var(--nx-faint)]">@svg('heroicon-o-swatch', 'w-[15px] h-[15px]') Mockup</span>
                @if($mockupUrl)
                    <a href="{{ $mockupUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[13.5px] text-[color:var(--nx-info)] hover:underline">Öffnen @svg('heroicon-o-arrow-top-right-on-square', 'w-3.5 h-3.5')</a>
                @else
                    @can('update', $brand)
                        <button type="button" @click="$dispatch('open-modal-brand-settings', { brandId: {{ $brand->id }} })" class="inline-flex items-center gap-1 text-[13.5px] text-[color:var(--nx-faint)] transition-colors hover:text-[color:var(--nx-text)]">@svg('heroicon-o-plus', 'w-3.5 h-3.5') hinterlegen</button>
                    @else
                        <span class="text-[13.5px] text-[color:var(--nx-faint)]">–</span>
                    @endcan
                @endif
            </div>
        </div>

        {{-- ══════════ IDENTITÄT ══════════ --}}
        @if($hasIdentity)
            <section class="mt-10">
                <div class="mb-4 flex items-baseline gap-2.5 border-b border-[color:var(--nx-line)] pb-2.5">
                    @svg('heroicon-o-swatch', 'w-[17px] h-[17px] text-[color:var(--nx-faint)] translate-y-[3px]')
                    <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-[color:var(--nx-muted)]">Identität</h2>
                </div>

                {{-- Farbwelt --}}
                @foreach($ciBoards as $ciB)
                    @php
                        $named = collect();
                        if ($ciB->primary_color) { $named->push(['n' => 'Primär', 'c' => $ciB->primary_color]); }
                        if ($ciB->secondary_color) { $named->push(['n' => 'Sekundär', 'c' => $ciB->secondary_color]); }
                        if ($ciB->accent_color) { $named->push(['n' => 'Akzent', 'c' => $ciB->accent_color]); }
                        foreach ($ciB->colors as $col) { $named->push(['n' => $col->title ?: 'Farbe', 'c' => $col->color]); }
                        $named = $named->filter(fn($x) => !empty($x['c']))->unique(fn($x) => mb_strtoupper($x['c']))->values();
                    @endphp
                    <a href="{{ route('brands.ci-boards.show', $ciB) }}" wire:navigate class="group block">
                        <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Farbwelt — CI @if($ciBoards->count() > 1)<span class="normal-case tracking-normal font-normal"> · {{ $ciB->name }}</span>@endif</p>
                        @if($named->isNotEmpty())
                            <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                @foreach($named->take(10) as $sw)
                                    <div>
                                        <div class="h-[88px] rounded-[8px] border border-[color:var(--nx-line)]" style="background-color: {{ $sw['c'] }};"></div>
                                        <div class="mt-2 text-[13.5px] font-medium text-[color:var(--nx-text)]">{{ $sw['n'] }}</div>
                                        <div class="text-[12px] uppercase tabular-nums text-[color:var(--nx-faint)]">{{ $sw['c'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-[8px] border border-dashed border-[color:var(--nx-line-strong)] px-5 py-6 text-center text-[13px] text-[color:var(--nx-faint)]">Noch keine Farben hinterlegt</div>
                        @endif
                    </a>
                    @if(!$loop->last)<div class="mt-6"></div>@endif
                @endforeach

                {{-- Typografie + Logo --}}
                @if($typographyBoards->isNotEmpty() || $logoBoards->isNotEmpty())
                    <div class="mt-7 grid grid-cols-1 gap-5 md:grid-cols-2">
                        @if($typographyBoards->isNotEmpty())
                            <div>
                                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Typografie</p>
                                @foreach($typographyBoards as $tB)
                                    @if($tB->entries->isNotEmpty())
                                        <a href="{{ route('brands.typography-boards.show', $tB) }}" wire:navigate class="block rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-4 py-4 transition-colors hover:bg-[color:var(--nx-hover)] {{ !$loop->first ? 'mt-3' : '' }}">
                                            @foreach($tB->entries->take(4) as $entry)
                                                @php
                                                    $cf = $catalogByFamily->get($entry->font_family);
                                                    $stack = "'" . $entry->font_family . "', " . ($cf ? ($fontFallbacks[$cf['category']] ?? 'sans-serif') : 'sans-serif');
                                                @endphp
                                                <div class="{{ !$loop->first ? 'mt-3.5 border-t border-[color:var(--nx-line)] pt-3.5' : '' }}">
                                                    <div class="truncate text-[color:var(--nx-text)]" style="font-family: {{ $stack }}; font-weight: {{ $entry->font_weight ?: 400 }}; font-size: 22px; line-height: 1.3;">{{ $entry->sample_text ?: 'Marken mit klarem Charakter — AaGg 0123' }}</div>
                                                    <div class="mt-1 text-[12px] text-[color:var(--nx-faint)]">{{ $entry->font_family }} · {{ $entry->font_weight ?: 400 }}@if($entry->role) · {{ $entry->role }}@endif</div>
                                                </div>
                                            @endforeach
                                        </a>
                                    @else
                                        <a href="{{ route('brands.typography-boards.show', $tB) }}" wire:navigate class="block rounded-[8px] border border-dashed border-[color:var(--nx-line-strong)] px-5 py-6 text-center text-[13px] text-[color:var(--nx-faint)] transition-colors hover:text-[color:var(--nx-muted)] {{ !$loop->first ? 'mt-3' : '' }}">
                                            Noch keine Schriften definiert
                                            <span class="mt-2.5 inline-block rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-1 text-[13px] text-[color:var(--nx-muted)]">Schriften hinzufügen</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @if($logoBoards->isNotEmpty())
                            <div>
                                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Logo</p>
                                @foreach($logoBoards as $lB)
                                    @if($lB->variants->isNotEmpty())
                                        <a href="{{ route('brands.logo-boards.show', $lB) }}" wire:navigate class="block rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-4 py-4 transition-colors hover:bg-[color:var(--nx-hover)] {{ !$loop->first ? 'mt-3' : '' }}">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($lB->variants->take(6) as $v)
                                                    <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2.5 py-1 text-[12.5px] text-[color:var(--nx-muted)]">{{ Str::limit($v->name, 20) }}</span>
                                                @endforeach
                                            </div>
                                        </a>
                                    @else
                                        <a href="{{ route('brands.logo-boards.show', $lB) }}" wire:navigate class="block rounded-[8px] border border-dashed border-[color:var(--nx-line-strong)] px-5 py-6 text-center text-[13px] text-[color:var(--nx-faint)] transition-colors hover:text-[color:var(--nx-muted)] {{ !$loop->first ? 'mt-3' : '' }}">
                                            Noch keine Logo-Varianten
                                            <span class="mt-2.5 inline-block rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-1 text-[13px] text-[color:var(--nx-muted)]">Variante hochladen</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Moodboard --}}
                @foreach($moodboardBoards as $mB)
                    <div class="mt-7">
                        <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Moodboard @if($mB->images->isNotEmpty())<span class="normal-case tracking-normal font-normal text-[color:var(--nx-faint)]"> · {{ $mB->images->count() }} {{ $mB->images->count() === 1 ? 'Bild' : 'Bilder' }}</span>@endif</p>
                        <a href="{{ route('brands.moodboard-boards.show', $mB) }}" wire:navigate class="block">
                            @if($mB->images->isNotEmpty())
                                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                                    @foreach($mB->images->take(12) as $img)
                                        @if($img->thumbnail_url)
                                            <div class="aspect-square overflow-hidden rounded-[6px] border border-[color:var(--nx-line)]"><img src="{{ $img->thumbnail_url }}" alt="{{ $img->title }}" class="h-full w-full object-cover"></div>
                                        @else
                                            <div class="flex aspect-square items-center justify-center rounded-[6px] bg-[color:var(--nx-accent-soft)]">@svg('heroicon-o-photo', 'w-5 h-5 text-[color:var(--nx-faint)]')</div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-[8px] border border-dashed border-[color:var(--nx-line-strong)] px-5 py-6 text-center text-[13px] text-[color:var(--nx-faint)]">Noch keine Bilder</div>
                            @endif
                        </a>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- ══════════ STRATEGIE ══════════ --}}
        @if($hasStrategie)
            <section class="mt-10">
                <div class="mb-4 flex items-baseline gap-2.5 border-b border-[color:var(--nx-line)] pb-2.5">
                    @svg('heroicon-o-light-bulb', 'w-[17px] h-[17px] text-[color:var(--nx-faint)] translate-y-[3px]')
                    <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-[color:var(--nx-muted)]">Strategie</h2>
                </div>

                {{-- Personas --}}
                @foreach($personaBoards as $pB)
                    <div class="{{ !$loop->first ? 'mt-7' : '' }}">
                        <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Personas @if($pB->personas->isNotEmpty())<span class="normal-case tracking-normal font-normal text-[color:var(--nx-faint)]"> · {{ $pB->personas->count() }}</span>@endif</p>
                        @if($pB->personas->isNotEmpty())
                            <div class="grid grid-cols-1 gap-3.5 md:grid-cols-2 lg:grid-cols-3">
                                @foreach($pB->personas->take(6) as $persona)
                                    @php $pi = collect(explode(' ', trim($persona->name)))->filter()->take(2)->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''); @endphp
                                    <a href="{{ route('brands.persona-boards.show', $pB) }}" wire:navigate class="block rounded-[12px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-[18px] transition-colors hover:bg-[color:var(--nx-hover)]">
                                        <div class="flex h-[38px] w-[38px] items-center justify-center rounded-full text-[14px] font-semibold text-white" style="background-color: {{ $primary ?: '#787774' }};">{{ $pi ?: '·' }}</div>
                                        <div class="mt-3 text-[15px] font-semibold text-[color:var(--nx-text)]">{{ $persona->name }}</div>
                                        @if($persona->occupation)<div class="mt-0.5 text-[13px] text-[color:var(--nx-faint)]">{{ $persona->occupation }}</div>@endif
                                        @if(!empty($persona->goals) || !empty($persona->pain_points))
                                            <div class="mt-3.5 space-y-3 border-t border-[color:var(--nx-line)] pt-3.5">
                                                @if(!empty($persona->goals))
                                                    <div>
                                                        <div class="text-[10.5px] uppercase tracking-[0.1em] text-[color:var(--nx-faint)]">Ziele</div>
                                                        <p class="mt-1 text-[12.5px] leading-snug text-[color:var(--nx-muted)]">{{ Str::limit(collect($persona->goals)->take(2)->implode(' · '), 80) }}</p>
                                                    </div>
                                                @endif
                                                @if(!empty($persona->pain_points))
                                                    <div>
                                                        <div class="text-[10.5px] uppercase tracking-[0.1em] text-[color:var(--nx-faint)]">Pain Points</div>
                                                        <p class="mt-1 text-[12.5px] leading-snug text-[color:var(--nx-muted)]">{{ Str::limit(collect($persona->pain_points)->take(2)->implode(' · '), 80) }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <a href="{{ route('brands.persona-boards.show', $pB) }}" wire:navigate class="block rounded-[8px] border border-dashed border-[color:var(--nx-line-strong)] px-5 py-6 text-center text-[13px] text-[color:var(--nx-faint)]">Noch keine Personas</a>
                        @endif
                    </div>
                @endforeach

                {{-- Tone of Voice + Wettbewerber --}}
                @if($toneOfVoiceBoards->isNotEmpty() || $competitorBoards->isNotEmpty())
                    <div class="mt-7 grid grid-cols-1 gap-5 md:grid-cols-2">
                        @if($toneOfVoiceBoards->isNotEmpty())
                            <div>
                                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Tone of Voice</p>
                                @foreach($toneOfVoiceBoards as $tvB)
                                    <a href="{{ route('brands.tone-of-voice-boards.show', $tvB) }}" wire:navigate class="block rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-5 py-[18px] transition-colors hover:bg-[color:var(--nx-hover)] {{ !$loop->first ? 'mt-3' : '' }}">
                                        @if($tvB->dimensions->isNotEmpty())
                                            @foreach($tvB->dimensions->take(6) as $dim)
                                                <div class="{{ !$loop->first ? 'mt-3.5' : '' }}">
                                                    <div class="mb-1.5 flex justify-between text-[12.5px] text-[color:var(--nx-muted)]"><span>{{ $dim->label_left }}</span><span>{{ $dim->label_right }}</span></div>
                                                    <div class="relative h-[5px] rounded-full bg-[color:var(--nx-accent-soft)]">
                                                        <span class="absolute top-1/2 h-[13px] w-[13px] -translate-x-1/2 -translate-y-1/2 rounded-full ring-[3px] ring-[color:var(--nx-surface)]" style="left: {{ max(0, min(100, $dim->value ?? 50)) }}%; background-color: {{ $primary ?: '#787774' }};"></span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @elseif($tvB->entries->isNotEmpty())
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($tvB->entries->take(6) as $entry)
                                                    <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2.5 py-1 text-[12.5px] text-[color:var(--nx-muted)]">{{ Str::limit($entry->name, 24) }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-[13px] text-[color:var(--nx-faint)]">Noch keine Dimensionen</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        @if($competitorBoards->isNotEmpty())
                            <div>
                                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Wettbewerber</p>
                                @foreach($competitorBoards as $cB)
                                    <div class="overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] {{ !$loop->first ? 'mt-3' : '' }}">
                                        @forelse($cB->competitors->take(6) as $comp)
                                            <a href="{{ route('brands.competitor-boards.show', $cB) }}" wire:navigate class="group flex items-center gap-3 border-b border-[color:var(--nx-line)] px-4 py-3 last:border-0 transition-colors hover:bg-[color:var(--nx-hover)]">
                                                <span class="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-[7px] bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">@svg('heroicon-o-flag', 'w-4 h-4')</span>
                                                <span class="min-w-0">
                                                    <span class="block text-[14px] font-medium text-[color:var(--nx-text)]">{{ $comp->name }}</span>
                                                    @if($comp->website_url)<span class="block truncate text-[12.5px] text-[color:var(--nx-faint)]">{{ parse_url($comp->website_url, PHP_URL_HOST) }}</span>@endif
                                                </span>
                                                <span class="flex-1"></span>
                                                <span class="text-[color:var(--nx-faint)] opacity-0 transition-opacity group-hover:opacity-100">&rsaquo;</span>
                                            </a>
                                        @empty
                                            <div class="px-4 py-6 text-center text-[13px] text-[color:var(--nx-faint)]">Noch keine Wettbewerber</div>
                                        @endforelse
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Guidelines --}}
                @foreach($guidelineBoards as $gB)
                    <div class="mt-7">
                        <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Guidelines @if($gB->chapters->isNotEmpty())<span class="normal-case tracking-normal font-normal text-[color:var(--nx-faint)]"> · {{ $gB->chapters->count() }} {{ $gB->chapters->count() === 1 ? 'Kapitel' : 'Kapitel' }}</span>@endif</p>
                        <div class="overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
                            @forelse($gB->chapters->take(8) as $chapter)
                                <a href="{{ route('brands.guideline-boards.show', $gB) }}" wire:navigate class="group flex items-center gap-3 border-b border-[color:var(--nx-line)] px-4 py-3 last:border-0 transition-colors hover:bg-[color:var(--nx-hover)]">
                                    <span class="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-[7px] bg-[color:var(--nx-accent-soft)] text-[12px] font-semibold tabular-nums text-[color:var(--nx-muted)]">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="min-w-0">
                                        <span class="block text-[14px] font-medium text-[color:var(--nx-text)]">{{ $chapter->title }}</span>
                                        @if($chapter->description)<span class="block truncate text-[12.5px] text-[color:var(--nx-faint)]">{{ Str::limit($chapter->description, 60) }}</span>@endif
                                    </span>
                                    <span class="flex-1"></span>
                                    @if(isset($chapter->entries_count))<span class="rounded-full bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-[12px] tabular-nums text-[color:var(--nx-muted)]">{{ $chapter->entries_count }}</span>@endif
                                    <span class="text-[color:var(--nx-faint)] opacity-0 transition-opacity group-hover:opacity-100">&rsaquo;</span>
                                </a>
                            @empty
                                <div class="px-4 py-6 text-center text-[13px] text-[color:var(--nx-faint)]">Noch keine Kapitel</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                {{-- Referenzen / Website-Benchmarks --}}
                @foreach($referenceBoards as $rB)
                    @php
                        $rbLiked = $rB->references->where('verdict', 'like');
                        $rbDisliked = $rB->references->where('verdict', 'dislike');
                    @endphp
                    <div class="mt-7">
                        <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">{{ $rB->name }}<span class="normal-case tracking-normal font-normal"> · <span class="text-emerald-600">{{ $rbLiked->count() }} gefällt</span> · <span class="text-rose-500">{{ $rbDisliked->count() }} gefällt nicht</span></span></p>
                        <a href="{{ route('brands.reference-boards.show', $rB) }}" wire:navigate class="block overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] transition-colors hover:bg-[color:var(--nx-hover)]">
                            @if($rB->references->isNotEmpty())
                                <div class="grid grid-cols-2 gap-px bg-[color:var(--nx-line)] sm:grid-cols-4">
                                    @foreach($rB->references->take(8) as $ref)
                                        <div class="relative bg-[color:var(--nx-surface)]">
                                            <div class="aspect-[16/10] w-full overflow-hidden bg-[color:var(--nx-accent-soft)]">
                                                @if($ref->screenshot_url)
                                                    <img src="{{ $ref->screenshot_url }}" alt="{{ $ref->host }}" class="h-full w-full object-cover" loading="lazy">
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center text-[color:var(--nx-faint)]">@svg('heroicon-o-globe-alt', 'w-6 h-6')</div>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1.5 px-2.5 py-2">
                                                <span class="h-2 w-2 shrink-0 rounded-full {{ $ref->verdict === 'like' ? 'bg-emerald-500' : ($ref->verdict === 'dislike' ? 'bg-rose-500' : 'bg-[color:var(--nx-line-strong)]') }}"></span>
                                                <span class="truncate text-[12px] text-[color:var(--nx-muted)]">{{ $ref->host }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="px-4 py-6 text-center text-[13px] text-[color:var(--nx-faint)]">Noch keine Referenzen</div>
                            @endif
                        </a>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- ══════════ CONTENT & ASSETS ══════════ --}}
        @if($hasOps)
            <section class="mt-10">
                <div class="mb-4 flex items-baseline gap-2.5 border-b border-[color:var(--nx-line)] pb-2.5">
                    @svg('heroicon-o-bars-3-bottom-left', 'w-[17px] h-[17px] text-[color:var(--nx-faint)] translate-y-[3px]')
                    <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-[color:var(--nx-muted)]">Content &amp; Assets</h2>
                </div>
                <div class="overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
                    @foreach($socialBoards as $b)
                        <a href="{{ route('brands.social-boards.show', $b) }}" wire:navigate class="group flex items-center gap-3.5 border-b border-[color:var(--nx-line)] px-[15px] py-3 last:border-0 transition-colors hover:bg-[color:var(--nx-hover)]">
                            <span class="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-[7px] bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">@svg('heroicon-o-share', 'w-4 h-4')</span>
                            <span class="min-w-0"><span class="block text-[14px] font-medium text-[color:var(--nx-text)]">{{ $b->name }}</span><span class="block text-[12.5px] text-[color:var(--nx-faint)]">Social · {{ $b->slots->count() }} Slots · {{ $b->cards_count }} Cards</span></span>
                            <span class="flex-1"></span>
                            <span class="whitespace-nowrap text-[12.5px] tabular-nums text-[color:var(--nx-faint)]">{{ optional($b->updated_at)->format('d.m.Y') }}</span>
                            <span class="text-[color:var(--nx-faint)] opacity-0 transition-opacity group-hover:opacity-100">&rsaquo;</span>
                        </a>
                    @endforeach
                    @foreach($kanbanBoards as $b)
                        <a href="{{ route('brands.kanban-boards.show', $b) }}" wire:navigate class="group flex items-center gap-3.5 border-b border-[color:var(--nx-line)] px-[15px] py-3 last:border-0 transition-colors hover:bg-[color:var(--nx-hover)]">
                            <span class="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-[7px] bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">@svg('heroicon-o-view-columns', 'w-4 h-4')</span>
                            <span class="min-w-0"><span class="block text-[14px] font-medium text-[color:var(--nx-text)]">{{ $b->name }}</span><span class="block text-[12.5px] text-[color:var(--nx-faint)]">Kanban · {{ $b->slots->count() }} Spalten · {{ $b->cards_count }} Cards</span></span>
                            <span class="flex-1"></span>
                            <span class="whitespace-nowrap text-[12.5px] tabular-nums text-[color:var(--nx-faint)]">{{ optional($b->updated_at)->format('d.m.Y') }}</span>
                            <span class="text-[color:var(--nx-faint)] opacity-0 transition-opacity group-hover:opacity-100">&rsaquo;</span>
                        </a>
                    @endforeach
                    @foreach($seoBoards as $b)
                        <a href="{{ route('brands.seo-boards.show', $b) }}" wire:navigate class="group flex items-center gap-3.5 border-b border-[color:var(--nx-line)] px-[15px] py-3 last:border-0 transition-colors hover:bg-[color:var(--nx-hover)]">
                            <span class="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-[7px] bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">@svg('heroicon-o-magnifying-glass', 'w-4 h-4')</span>
                            <span class="min-w-0"><span class="block text-[14px] font-medium text-[color:var(--nx-text)]">{{ $b->name }}</span><span class="block text-[12.5px] text-[color:var(--nx-faint)]">SEO · {{ $b->keywords_count }} Keywords @if($b->keywordClusters->isNotEmpty())· {{ $b->keywordClusters->count() }} Cluster @endif</span></span>
                            <span class="flex-1"></span>
                            <span class="whitespace-nowrap text-[12.5px] tabular-nums text-[color:var(--nx-faint)]">{{ optional($b->updated_at)->format('d.m.Y') }}</span>
                            <span class="text-[color:var(--nx-faint)] opacity-0 transition-opacity group-hover:opacity-100">&rsaquo;</span>
                        </a>
                    @endforeach
                    @foreach($contentBriefBoards as $b)
                        <a href="{{ route('brands.content-brief-boards.show', $b) }}" wire:navigate class="group flex items-center gap-3.5 border-b border-[color:var(--nx-line)] px-[15px] py-3 last:border-0 transition-colors hover:bg-[color:var(--nx-hover)]">
                            <span class="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-[7px] bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">@svg('heroicon-o-document-text', 'w-4 h-4')</span>
                            <span class="min-w-0"><span class="block text-[14px] font-medium text-[color:var(--nx-text)]">{{ $b->name }}</span><span class="block text-[12.5px] {{ $b->content_type ? 'text-[color:var(--nx-faint)]' : 'text-[color:var(--nx-faint)]' }}">Content Brief @if($b->content_type)· {{ $b->content_type }}@else· kein Typ gesetzt @endif</span></span>
                            <span class="flex-1"></span>
                            <span class="whitespace-nowrap text-[12.5px] tabular-nums text-[color:var(--nx-faint)]">{{ optional($b->updated_at)->format('d.m.Y') }}</span>
                            <span class="text-[color:var(--nx-faint)] opacity-0 transition-opacity group-hover:opacity-100">&rsaquo;</span>
                        </a>
                    @endforeach
                    @foreach($assetBoards as $b)
                        <a href="{{ route('brands.asset-boards.show', $b) }}" wire:navigate class="group flex items-center gap-3.5 border-b border-[color:var(--nx-line)] px-[15px] py-3 last:border-0 transition-colors hover:bg-[color:var(--nx-hover)]">
                            <span class="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-[7px] bg-[color:var(--nx-accent-soft)] text-[color:var(--nx-muted)]">@svg('heroicon-o-folder-open', 'w-4 h-4')</span>
                            <span class="min-w-0"><span class="block text-[14px] font-medium text-[color:var(--nx-text)]">{{ $b->name }}</span><span class="block text-[12.5px] text-[color:var(--nx-faint)]">Assets · {{ $b->assets_count }} {{ $b->assets_count === 1 ? 'Datei' : 'Dateien' }}</span></span>
                            <span class="flex-1"></span>
                            <span class="whitespace-nowrap text-[12.5px] tabular-nums text-[color:var(--nx-faint)]">{{ optional($b->updated_at)->format('d.m.Y') }}</span>
                            <span class="text-[color:var(--nx-faint)] opacity-0 transition-opacity group-hover:opacity-100">&rsaquo;</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ══════════ LEERZUSTAND ══════════ --}}
        @if(!$hasIdentity && !$hasStrategie && !$hasOps && $accountsCount === 0)
            <div class="mt-10 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-6 py-12 text-center">
                @svg('heroicon-o-squares-2x2', 'w-8 h-8 mx-auto text-[color:var(--nx-faint)] opacity-50')
                <p class="mt-3 text-[13px] text-[color:var(--nx-faint)]">Noch keine Boards – lege über „Board erstellen" das erste Board an.</p>
            </div>
        @endif

        {{-- ══════════ SOCIAL ACCOUNTS ══════════ --}}
        @if($accountsCount > 0 || ($metaConnection && ($availableFacebookPages->count() > 0 || $availableInstagramAccounts->count() > 0)))
            <section class="mt-10">
                <div class="mb-4 flex items-baseline gap-2.5 border-b border-[color:var(--nx-line)] pb-2.5">
                    @svg('heroicon-o-globe-alt', 'w-[17px] h-[17px] text-[color:var(--nx-faint)] translate-y-[3px]')
                    <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-[color:var(--nx-muted)]">Social Accounts</h2>
                    <span class="text-xs tabular-nums text-[color:var(--nx-faint)]">{{ $accountsCount }} verknüpft</span>
                </div>

                @if($accountsCount > 0)
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($facebookPages as $facebookPage)
                            <div class="flex items-center gap-3 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[6px] bg-[rgba(25,113,194,.1)]">@svg('heroicon-o-globe-alt', 'w-4 h-4 text-[color:var(--nx-info)]')</span>
                                <a href="{{ route('brands.facebook-pages.show', $facebookPage) }}" wire:navigate class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $facebookPage->name }}</span><span class="block text-xs text-[color:var(--nx-faint)]">Facebook Page</span></a>
                                @can('update', $brand)
                                    <button wire:click="detachFacebookPage({{ $facebookPage->id }})" wire:confirm="Facebook Page wirklich trennen?" class="shrink-0 rounded p-1.5 text-[color:var(--nx-faint)] transition-colors hover:bg-[rgba(224,49,49,.08)] hover:text-[color:var(--nx-danger)]" title="Trennen">@svg('heroicon-o-x-mark', 'w-4 h-4')</button>
                                @endcan
                            </div>
                        @endforeach
                        @foreach($instagramAccounts as $instagramAccount)
                            <div class="flex items-center gap-3 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[6px] bg-[rgba(224,49,49,.1)]">@svg('heroicon-o-camera', 'w-4 h-4 text-[color:var(--nx-danger)]')</span>
                                <a href="{{ route('brands.instagram-accounts.show', $instagramAccount) }}" wire:navigate class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-[color:var(--nx-text)]">{{ '@' . $instagramAccount->username }}</span><span class="block text-xs text-[color:var(--nx-faint)]">Instagram</span></a>
                                @can('update', $brand)
                                    <button wire:click="detachInstagramAccount({{ $instagramAccount->id }})" wire:confirm="Instagram Account wirklich trennen?" class="shrink-0 rounded p-1.5 text-[color:var(--nx-faint)] transition-colors hover:bg-[rgba(224,49,49,.08)] hover:text-[color:var(--nx-danger)]" title="Trennen">@svg('heroicon-o-x-mark', 'w-4 h-4')</button>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($metaConnection && ($availableFacebookPages->count() > 0 || $availableInstagramAccounts->count() > 0))
                    @php $availableCount = $availableFacebookPages->count() + $availableInstagramAccounts->count(); @endphp
                    <div x-data="{ open: false }" class="{{ $accountsCount > 0 ? 'mt-4' : '' }}">
                        <button type="button" @click="open = !open" class="flex items-center gap-1.5 text-sm text-[color:var(--nx-muted)] transition-colors hover:text-[color:var(--nx-text)]">
                            <svg class="h-4 w-4 transition-transform" :class="open && 'rotate-90'" viewBox="0 0 20 20" fill="none"><path d="M7 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Weitere Accounts verknüpfen ({{ $availableCount }})
                        </button>
                        <div x-show="open" x-cloak class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($availableFacebookPages as $facebookPage)
                                <div class="flex items-center gap-3 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[6px] bg-[rgba(25,113,194,.1)]">@svg('heroicon-o-globe-alt', 'w-4 h-4 text-[color:var(--nx-info)]')</span>
                                    <div class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $facebookPage->name }}</span><span class="block text-xs text-[color:var(--nx-faint)]">Facebook Page</span></div>
                                    @can('update', $brand)
                                        <x-nx-button variant="secondary" size="sm" wire:click="attachFacebookPage({{ $facebookPage->id }})" class="shrink-0">@svg('heroicon-o-plus', 'w-3.5 h-3.5') Verknüpfen</x-nx-button>
                                    @endcan
                                </div>
                            @endforeach
                            @foreach($availableInstagramAccounts as $instagramAccount)
                                <div class="flex items-center gap-3 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[6px] bg-[rgba(224,49,49,.1)]">@svg('heroicon-o-camera', 'w-4 h-4 text-[color:var(--nx-danger)]')</span>
                                    <div class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-[color:var(--nx-text)]">{{ '@' . $instagramAccount->username }}</span><span class="block text-xs text-[color:var(--nx-faint)]">Instagram</span></div>
                                    @can('update', $brand)
                                        <x-nx-button variant="secondary" size="sm" wire:click="attachInstagramAccount({{ $instagramAccount->id }})" class="shrink-0">@svg('heroicon-o-plus', 'w-3.5 h-3.5') Verknüpfen</x-nx-button>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif

    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Marken-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Dashboard</h3>
                    <div class="space-y-3">
                        <x-nx-stat label="Boards" :value="$totalBoards" icon="heroicon-o-squares-2x2" />
                        @if($accountsCount > 0)
                            <x-nx-stat label="Social Accounts" :value="$accountsCount" icon="heroicon-o-share" />
                        @endif
                        <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-4">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 text-xs font-medium text-[color:var(--nx-muted)]">@svg('heroicon-o-link', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')<span>Meta Connection</span></div>
                                @if($metaConnection)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-[color:var(--nx-success)]"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>Aktiv</span>
                                @else
                                    <span class="text-xs font-medium text-[color:var(--nx-faint)]">Nicht verbunden</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Details</h3>
                    <div class="overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] divide-y divide-[color:var(--nx-line)]">
                        @if($verortung)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Verortung</span>
                                <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $verortung['entity'] }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Erstellt</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $brand->created_at->format('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">@include('brands::partials.board-activity')</x-slot>

    <livewire:brands.brand-settings-modal/>
    <livewire:brands.facebook-page-modal/>
</x-ui-page>
