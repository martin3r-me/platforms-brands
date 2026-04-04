<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Marken" icon="heroicon-o-tag" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'icon' => 'tag'],
        ]">
            <x-ui-button variant="primary" size="sm" wire:click="createBrand">
                @svg('heroicon-o-plus', 'w-4 h-4')
                <span>Neue Marke</span>
            </x-ui-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-0">

        {{-- Hero Stats --}}
        <div class="py-12 md:py-16">
            <div class="flex items-baseline gap-6 flex-wrap">
                <div>
                    <span class="text-5xl md:text-6xl font-light text-gray-900 tracking-tight">{{ $activeBrands }}</span>
                    <span class="text-lg text-gray-400 ml-2">{{ $activeBrands === 1 ? 'Marke' : 'Marken' }}</span>
                </div>
                <div class="flex items-baseline gap-6 text-base text-gray-300">
                    @if($totalBrands > $activeBrands)
                        <span>{{ $totalBrands - $activeBrands }} archiviert</span>
                    @endif
                    <span>{{ $totalBoards }} Boards</span>
                </div>
            </div>
        </div>

        {{-- Brand Cards als 3er Grid --}}
        @if($activeBrandsList->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($activeBrandsList as $brand)
                    @php
                        $ciBoard = $brand->ciBoards->first();
                        $moodboard = $brand->moodboardBoards->first();
                        $typographyBoard = $brand->typographyBoards->first();
                        $boardCount = $brand->ciBoards->count() + $brand->socialBoards->count() + $brand->kanbanBoards->count()
                            + $brand->typographyBoards->count() + $brand->logoBoards->count() + $brand->toneOfVoiceBoards->count()
                            + $brand->personaBoards->count() + $brand->competitorBoards->count() + $brand->guidelineBoards->count()
                            + $brand->moodboardBoards->count() + $brand->seoBoards->count() + $brand->assetBoards->count()
                            + $brand->contentBriefBoards->count();
                    @endphp
                    <a href="{{ route('brands.brands.show', $brand) }}" wire:navigate
                       class="group relative flex flex-col bg-white rounded-2xl border border-gray-100 hover:border-gray-200 hover:shadow-lg transition-all duration-300 overflow-hidden">

                        {{-- Color Bar aus CI --}}
                        @if($ciBoard && $ciBoard->primary_color)
                            <div class="h-1.5 flex">
                                <div class="flex-1" style="background-color: {{ $ciBoard->primary_color }};"></div>
                                @if($ciBoard->secondary_color)
                                    <div class="flex-1" style="background-color: {{ $ciBoard->secondary_color }};"></div>
                                @endif
                                @if($ciBoard->accent_color)
                                    <div class="flex-1" style="background-color: {{ $ciBoard->accent_color }};"></div>
                                @endif
                            </div>
                        @else
                            <div class="h-1.5 bg-gray-100"></div>
                        @endif

                        <div class="flex-1 p-6">
                            {{-- Brand Name --}}
                            <h2 class="text-xl font-semibold tracking-tight text-gray-900">{{ $brand->name }}</h2>

                            @if($brand->description)
                                <p class="text-sm text-gray-400 leading-relaxed mt-1.5 line-clamp-2">{{ $brand->description }}</p>
                            @endif

                            {{-- Slogan --}}
                            @if($ciBoard && ($ciBoard->slogan || $ciBoard->tagline))
                                <p class="text-sm text-gray-500 italic mt-3">&ldquo;{{ Str::limit($ciBoard->slogan ?: $ciBoard->tagline, 60) }}&rdquo;</p>
                            @endif

                            {{-- Preview Elements --}}
                            <div class="mt-5 space-y-4">

                                {{-- Moodboard Thumbnails --}}
                                @if($moodboard && $moodboard->images->isNotEmpty())
                                    <div class="flex items-center gap-2">
                                        @foreach($moodboard->images->take(4) as $image)
                                            @if($image->thumbnail_url)
                                                <div class="w-14 h-14 rounded-xl overflow-hidden bg-gray-100">
                                                    <img src="{{ $image->thumbnail_url }}" alt="{{ $image->title }}" class="w-full h-full object-cover">
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                {{-- CI-Farben + Typografie nebeneinander --}}
                                <div class="flex items-center gap-5">
                                    @if($ciBoard)
                                        <div class="flex items-center">
                                            @if($ciBoard->primary_color)
                                                <div class="w-8 h-8 rounded-full ring-2 ring-white shadow-sm" style="background-color: {{ $ciBoard->primary_color }};"></div>
                                            @endif
                                            @if($ciBoard->secondary_color)
                                                <div class="w-8 h-8 rounded-full ring-2 ring-white shadow-sm -ml-2" style="background-color: {{ $ciBoard->secondary_color }};"></div>
                                            @endif
                                            @if($ciBoard->accent_color)
                                                <div class="w-8 h-8 rounded-full ring-2 ring-white shadow-sm -ml-2" style="background-color: {{ $ciBoard->accent_color }};"></div>
                                            @endif
                                            @foreach($ciBoard->colors->take(2) as $color)
                                                <div class="w-8 h-8 rounded-full ring-2 ring-white shadow-sm -ml-2" style="background-color: {{ $color->color }};"></div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($typographyBoard && $typographyBoard->entries->isNotEmpty())
                                        <div class="min-w-0">
                                            @foreach($typographyBoard->entries->take(2) as $entry)
                                                <span class="text-sm font-semibold text-gray-500 block leading-snug truncate">{{ $entry->font_family }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="px-6 py-3.5 border-t border-gray-50 flex items-center justify-between">
                            <div class="flex items-center gap-3 text-[12px] text-gray-300">
                                <span>{{ $boardCount }} Boards</span>
                                <span>{{ $brand->updated_at->format('d. M') }}</span>
                            </div>
                            <span class="text-xs text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                Öffnen &rarr;
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            {{-- Empty State --}}
            <div class="py-20 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-50 mb-6">
                    @svg('heroicon-o-tag', 'w-10 h-10 text-gray-300')
                </div>
                <h3 class="text-2xl font-light text-gray-900 mb-2">Noch keine Marken</h3>
                <p class="text-base text-gray-400 mb-8">Erstelle deine erste Marke, um loszulegen.</p>
                <x-ui-button variant="primary" size="sm" wire:click="createBrand">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Neue Marke</span>
                </x-ui-button>
            </div>
        @endif

        {{-- Archivierte Marken --}}
        @if($doneBrandsList->isNotEmpty())
            <div class="border-t border-gray-100 pt-12 mt-4">
                <span class="text-[11px] uppercase tracking-[0.2em] font-medium text-gray-300">Archiviert</span>
                <div class="mt-4 space-y-0 divide-y divide-gray-50">
                    @foreach($doneBrandsList as $brand)
                        <a href="{{ route('brands.brands.show', $brand) }}" wire:navigate
                           class="group flex items-center justify-between py-4 px-2 md:px-6 hover:bg-gray-50/50 transition-colors duration-300">
                            <div class="min-w-0">
                                <span class="text-lg text-gray-400 font-light">{{ $brand->name }}</span>
                                @if($brand->description)
                                    <span class="text-sm text-gray-300 ml-3 hidden md:inline">{{ Str::limit($brand->description, 60) }}</span>
                                @endif
                            </div>
                            <span class="text-sm text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                                Öffnen &rarr;
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    </x-ui-page-container>
</x-ui-page>
