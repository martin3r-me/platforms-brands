<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="'@' . $instagramAccount->username" icon="heroicon-o-camera" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $instagramAccount->brand->name ?? 'Marke', 'href' => $instagramAccount->brand ? route('brands.brands.show', $instagramAccount->brand) : '#'],
            ['label' => '@' . $instagramAccount->username],
        ]">
            @can('update', $instagramAccount)
                <x-nx-button variant="primary" size="sm" wire:click="syncMedia">
                    @svg('heroicon-o-arrow-path', 'w-4 h-4')
                    <span>Media synchronisieren</span>
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">
        {{-- Hero Section --}}
        <div>
            <div class="grid lg:grid-cols-2 gap-12 items-start">
                {{-- Linke Spalte: Profilinfo --}}
                <div class="space-y-8">
                    <div class="flex items-start gap-8">
                        {{-- Profilbild --}}
                        <div class="relative group">
                            <div class="flex h-32 w-32 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)] ring-4 ring-[color:var(--nx-surface)]">
                                @svg('heroicon-o-camera', 'w-16 h-16 text-[color:var(--nx-muted)]')
                            </div>
                        </div>

                        <div class="flex-1">
                            {{-- Username und Verifizierung --}}
                            <div class="mb-1 flex items-center gap-2">
                                <h1 class="text-2xl font-semibold text-[color:var(--nx-text)]">{{ '@' . $instagramAccount->username }}</h1>
                                <x-nx-badge variant="info">
                                    @svg('heroicon-o-check-badge', 'w-3.5 h-3.5')
                                    Verifiziert
                                </x-nx-badge>
                            </div>

                            {{-- Name und Bio --}}
                            <div class="mt-4 space-y-2">
                                @if($latestInsights && $latestInsights->current_name)
                                    <h2 class="text-base font-semibold text-[color:var(--nx-text)]">{{ $latestInsights->current_name }}</h2>
                                @endif
                                @if($latestInsights && $latestInsights->current_biography)
                                    <p class="whitespace-pre-line text-sm leading-relaxed text-[color:var(--nx-muted)]">
                                        {{ $latestInsights->current_biography }}
                                    </p>
                                @elseif($instagramAccount->description)
                                    <p class="whitespace-pre-line text-sm leading-relaxed text-[color:var(--nx-muted)]">
                                        {{ $instagramAccount->description }}
                                    </p>
                                @endif
                            </div>

                            {{-- Action Button --}}
                            <div class="mt-6">
                                <x-nx-button variant="secondary" size="md" :href="'https://instagram.com/' . $instagramAccount->username" target="_blank">
                                    @svg('heroicon-o-arrow-top-right-on-square', 'w-4 h-4')
                                    Auf Instagram ansehen
                                </x-nx-button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rechte Spalte: Statistiken --}}
                <div class="grid grid-cols-2 gap-4">
                    <x-nx-stat label="Follower" icon="heroicon-o-users"
                               :value="$latestInsights ? number_format($latestInsights->current_followers ?? $latestInsights->follower_count ?? 0) : '0'" />
                    <x-nx-stat label="Following" icon="heroicon-o-user-group"
                               :value="$latestInsights ? number_format($latestInsights->current_follows ?? 0) : '0'" />
                    <x-nx-stat label="Likes" icon="heroicon-o-heart"
                               :value="number_format($media->sum('like_count'))" />
                    <x-nx-stat label="Kommentare" icon="heroicon-o-chat-bubble-left"
                               :value="number_format($media->sum('comments_count'))" />
                    <x-nx-stat label="Gesamt Posts" icon="heroicon-o-photo" class="col-span-2"
                               :value="number_format($media->count())" />
                </div>
            </div>
        </div>

        {{-- Latest Post Performance --}}
        @if($lastPost = $media->first())
            <x-nx-section icon="heroicon-o-photo" title="Letzter Post"
                          :description="'Veröffentlicht: ' . ($lastPost->timestamp ? $lastPost->timestamp->format('d.m.Y, H:i') : 'Unbekannt') . ' Uhr'">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    {{-- Post Preview --}}
                    <x-nx-card flush class="overflow-hidden">
                        {{-- Post Header --}}
                        <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] p-2">
                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)]">
                                @svg('heroicon-o-camera', 'w-3 h-3 text-[color:var(--nx-muted)]')
                            </div>
                            <div class="flex items-center gap-2">
                                <p class="truncate text-xs font-medium text-[color:var(--nx-text)]">
                                    {{ $instagramAccount->username }}
                                </p>
                                @if($lastPost->contextFiles->count() > 1)
                                    <div class="flex items-center gap-1 text-[color:var(--nx-faint)]">
                                        @svg('heroicon-o-squares-2x2', 'w-3 h-3')
                                        <span class="text-xs">{{ $lastPost->contextFiles->count() }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Media Content --}}
                        @php
                            $primaryFile = $lastPost->contextFiles->where('meta.role', 'primary')->first();
                            $aspectRatio = 'aspect-square';
                            if ($primaryFile && $primaryFile->width && $primaryFile->height) {
                                $ratio = $primaryFile->width / $primaryFile->height;
                                if ($ratio > 1.2) {
                                    $aspectRatio = 'aspect-video';
                                } elseif ($ratio < 0.8) {
                                    $aspectRatio = 'aspect-[4/5]';
                                }
                            }
                        @endphp
                        <div class="relative bg-[color:var(--nx-hover)] {{ $aspectRatio }} overflow-hidden">
                            @if($lastPost->media_type === 'CAROUSEL_ALBUM' && $lastPost->contextFiles->where('meta.role', 'carousel')->count() > 0)
                                {{-- Carousel Album --}}
                                @php
                                    $carouselItems = $lastPost->contextFiles->where('meta.role', 'carousel')->sortBy(function($file) {
                                        return $file->meta['carousel_index'] ?? 999;
                                    });
                                @endphp
                                <div x-data="{ activeIndex: 0 }" class="absolute inset-0 h-full w-full">
                                    @foreach($carouselItems as $index => $carouselFile)
                                        <div @if($index === 0) x-show.immediate="activeIndex === {{ $index }}" @else x-show="activeIndex === {{ $index }}" @endif
                                             x-transition:enter="transition ease-out duration-300"
                                             x-transition:enter-start="opacity-0"
                                             x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-300"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             class="absolute inset-0 h-full w-full">
                                            @if($carouselFile->isImage())
                                                <img src="{{ route('core.context-files.show', ['token' => $carouselFile->token]) }}"
                                                     alt="Instagram Carousel Image {{ $index + 1 }}"
                                                     class="h-full w-full object-cover">
                                            @else
                                                <video class="h-full w-full object-cover"
                                                       playsinline autoplay muted loop preload="auto">
                                                    <source src="{{ route('core.context-files.show', ['token' => $carouselFile->token]) }}" type="{{ $carouselFile->mime_type }}">
                                                </video>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if($carouselItems->count() > 1)
                                        {{-- Navigation Arrows --}}
                                        <button @click="activeIndex = activeIndex === 0 ? {{ $carouselItems->count() - 1 }} : activeIndex - 1"
                                                class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 transition-colors z-10">
                                            @svg('heroicon-o-chevron-left', 'w-5 h-5')
                                        </button>
                                        <button @click="activeIndex = activeIndex === {{ $carouselItems->count() - 1 }} ? 0 : activeIndex + 1"
                                                class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 transition-colors z-10">
                                            @svg('heroicon-o-chevron-right', 'w-5 h-5')
                                        </button>

                                        {{-- Dots Indicator --}}
                                        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5 z-10">
                                            @foreach($carouselItems as $index => $item)
                                                <button @click="activeIndex = {{ $index }}"
                                                        class="w-1.5 h-1.5 rounded-full transition-all"
                                                        :class="activeIndex === {{ $index }} ? 'bg-white w-4' : 'bg-white/50'">
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @elseif($lastPost->media_type === 'VIDEO')
                                {{-- Video --}}
                                @if($lastPost->contextFiles->where('meta.role', 'primary')->first())
                                    @php $primaryFile = $lastPost->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                    <video class="absolute inset-0 h-full w-full object-cover"
                                           playsinline autoplay muted loop preload="auto">
                                        <source src="{{ route('core.context-files.show', ['token' => $primaryFile->token]) }}" type="{{ $primaryFile->mime_type }}">
                                    </video>
                                    @endif
                            @elseif($lastPost->media_type === 'IMAGE' || !$lastPost->media_type)
                                {{-- Single Image --}}
                                @if($lastPost->contextFiles->where('meta.role', 'primary')->first())
                                    @php $primaryFile = $lastPost->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                    <img src="{{ route('core.context-files.show', ['token' => $primaryFile->token]) }}"
                                         alt="Instagram Image"
                                         class="absolute inset-0 h-full w-full object-cover">
                                @endif
                                @endif
                            </div>

                            {{-- Post Stats --}}
                            <div class="border-t border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
                                <div class="px-3 py-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                            <span class="text-sm font-medium">{{ number_format($lastPost->like_count) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                            <span class="text-sm font-medium text-[color:var(--nx-text)]">{{ number_format($lastPost->comments_count) }}</span>
                                        </div>
                                    </div>
                                    @if($lastPost->timestamp)
                                        <span class="text-xs text-[color:var(--nx-faint)]">{{ $lastPost->timestamp->format('d.m.Y') }}</span>
                                    @endif
                                </div>
                                @if($lastPost->caption)
                                    <div class="px-3 py-2 border-t border-[color:var(--nx-line)]">
                                        <p class="text-sm text-[color:var(--nx-text)] whitespace-pre-line line-clamp-2">{{ $lastPost->caption }}</p>
                                    </div>
                                @endif
                        </div>
                    </x-nx-card>

                    {{-- Post Stats --}}
                    <div class="space-y-6">
                        {{-- Quick Stats --}}
                        <div class="grid grid-cols-2 gap-4">
                            <x-nx-stat label="Likes" icon="heroicon-o-heart" :value="number_format($lastPost->like_count)" />
                            <x-nx-stat label="Kommentare" icon="heroicon-o-chat-bubble-left" :value="number_format($lastPost->comments_count)" />

                            {{-- Insights Stats --}}
                            @if($lastPost->latestInsight)
                                <x-nx-stat label="Gespeichert" icon="heroicon-o-bookmark" :value="number_format($lastPost->latestInsight->saved ?? 0)" />
                                <x-nx-stat label="Geteilt" icon="heroicon-o-share" :value="number_format($lastPost->latestInsight->shares ?? 0)" />
                            @endif
                        </div>

                        {{-- Performance Metrics --}}
                        @if($lastPost->latestInsight)
                            <x-nx-card class="space-y-4">
                                <h4 class="text-sm font-semibold text-[color:var(--nx-text)]">Performance</h4>
                                <div class="grid gap-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            @svg('heroicon-o-eye', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                            <span class="text-[color:var(--nx-muted)]">Reichweite</span>
                                        </div>
                                        <span class="font-medium text-[color:var(--nx-text)]">{{ number_format($lastPost->latestInsight->reach ?? 0) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            @svg('heroicon-o-cursor-arrow-rays', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                            <span class="text-[color:var(--nx-muted)]">Impressionen</span>
                                        </div>
                                        <span class="font-medium text-[color:var(--nx-text)]">{{ number_format($lastPost->latestInsight->impressions ?? 0) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            @svg('heroicon-o-hand-raised', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                            <span class="text-[color:var(--nx-muted)]">Interaktionen</span>
                                        </div>
                                        <span class="font-medium text-[color:var(--nx-text)]">{{ number_format($lastPost->latestInsight->total_interactions ?? 0) }}</span>
                                    </div>
                                </div>
                            </x-nx-card>
                        @endif

                        {{-- Post Details --}}
                        <x-nx-card class="space-y-3">
                            <h4 class="text-sm font-semibold text-[color:var(--nx-text)]">Post Details</h4>
                            <div class="grid gap-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-[color:var(--nx-muted)]">Typ</span>
                                    <span class="font-medium text-[color:var(--nx-text)]">{{ $lastPost->media_type }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[color:var(--nx-muted)]">Story</span>
                                    <span class="font-medium text-[color:var(--nx-text)]">{{ $lastPost->is_story ? 'Ja' : 'Nein' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[color:var(--nx-muted)]">Medien</span>
                                    <span class="font-medium text-[color:var(--nx-text)]">{{ $lastPost->contextFiles->count() }}</span>
                                </div>
                                @if($lastPost->permalink)
                                    <x-nx-button variant="primary" size="md" :href="$lastPost->permalink" target="_blank" class="mt-2">
                                        @svg('heroicon-o-arrow-top-right-on-square', 'w-4 h-4')
                                        Auf Instagram ansehen
                                    </x-nx-button>
                                @endif
                            </div>
                        </x-nx-card>
                    </div>
                </div>
            </x-nx-section>
        @endif

        {{-- Performance Insights --}}
        @if($latestInsights)
            <x-nx-section icon="heroicon-o-chart-bar" title="Account Performance"
                          :description="'Letztes Update: ' . $latestInsights->updated_at->format('d.m.Y, H:i') . ' Uhr'">
                <x-slot name="action">
                    <x-nx-badge variant="info">Letzter Tag</x-nx-badge>
                </x-slot>

                {{-- Performance Grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Reichweite & Sichtbarkeit --}}
                    <div class="lg:col-span-1">
                        <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-[color:var(--nx-text)]">
                            @svg('heroicon-o-eye', 'w-5 h-5 text-[color:var(--nx-faint)]')
                            Reichweite &amp; Sichtbarkeit
                        </h3>

                        <div class="space-y-4">
                            <x-nx-card>
                                <div class="flex h-full items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-[color:var(--nx-muted)]">Reichweite</h4>
                                        <p class="mt-1 text-2xl font-bold text-[color:var(--nx-text)]">{{ number_format($latestInsights->reach ?? 0) }}</p>
                                    </div>
                                    <div class="flex items-center text-[color:var(--nx-faint)]">
                                        @svg('heroicon-o-users', 'w-5 h-5')
                                        <span class="ml-1 text-xs">Unique Accounts</span>
                                    </div>
                                </div>
                            </x-nx-card>

                            <x-nx-card>
                                <div class="flex h-full items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-[color:var(--nx-muted)]">Impressionen</h4>
                                        <p class="mt-1 text-2xl font-bold text-[color:var(--nx-text)]">{{ number_format($latestInsights->impressions ?? 0) }}</p>
                                    </div>
                                    <div class="flex items-center text-[color:var(--nx-faint)]">
                                        @svg('heroicon-o-eye', 'w-5 h-5')
                                        <span class="ml-1 text-xs">Gesamte Ansichten</span>
                                    </div>
                                </div>
                            </x-nx-card>
                        </div>
                    </div>

                    {{-- Engagement --}}
                    <div class="lg:col-span-1">
                        <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-[color:var(--nx-text)]">
                            @svg('heroicon-o-heart', 'w-5 h-5 text-[color:var(--nx-faint)]')
                            Engagement
                        </h3>

                        <div class="space-y-4">
                            <x-nx-card>
                                <div class="mb-3 flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-[color:var(--nx-muted)]">Interaktionen</h4>
                                        <p class="mt-1 text-2xl font-bold text-[color:var(--nx-text)]">{{ number_format($latestInsights->total_interactions ?? 0) }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                        <span class="text-sm text-[color:var(--nx-muted)]">{{ number_format($latestInsights->likes ?? 0) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-blue-500')
                                        <span class="text-sm text-[color:var(--nx-muted)]">{{ number_format($latestInsights->comments ?? 0) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-bookmark', 'w-4 h-4 text-amber-500')
                                        <span class="text-sm text-[color:var(--nx-muted)]">{{ number_format($latestInsights->saves ?? 0) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-share', 'w-4 h-4 text-green-500')
                                        <span class="text-sm text-[color:var(--nx-muted)]">{{ number_format($latestInsights->shares ?? 0) }}</span>
                                    </div>
                                </div>
                            </x-nx-card>

                            <x-nx-card>
                                <div class="flex h-full items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-[color:var(--nx-muted)]">Profilaufrufe</h4>
                                        <p class="mt-1 text-2xl font-bold text-[color:var(--nx-text)]">{{ number_format($latestInsights->profile_views ?? 0) }}</p>
                                    </div>
                                    <div class="flex items-center text-[color:var(--nx-faint)]">
                                        @svg('heroicon-o-user', 'w-5 h-5')
                                    </div>
                                </div>
                            </x-nx-card>
                        </div>
                    </div>

                    {{-- Engagement Rate --}}
                    <div class="lg:col-span-1">
                        <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-[color:var(--nx-text)]">
                            @svg('heroicon-o-chart-bar', 'w-5 h-5 text-[color:var(--nx-faint)]')
                            Engagement Rate
                        </h3>

                        <div class="space-y-4">
                            <x-nx-card>
                                <div class="flex h-full items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-[color:var(--nx-muted)]">Durchschnittlich</h4>
                                        @php
                                            $followers = $latestInsights->current_followers ?? $latestInsights->follower_count ?? 0;
                                            $totalEngagements = ($latestInsights->likes ?? 0) + ($latestInsights->comments ?? 0) + ($latestInsights->saves ?? 0) + ($latestInsights->shares ?? 0);
                                            $engagementRate = $followers > 0 ? ($totalEngagements / $followers) * 100 : 0;
                                        @endphp
                                        <p class="mt-1 text-2xl font-bold text-[color:var(--nx-text)]">
                                            {{ number_format($engagementRate, 1) }}%
                                        </p>
                                    </div>
                                    <div class="flex items-center text-[color:var(--nx-faint)]">
                                        @svg('heroicon-o-arrow-trending-up', 'w-5 h-5')
                                        <span class="ml-1 text-xs">pro Follower</span>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-[color:var(--nx-faint)]">
                                    <div>Engagements: {{ number_format($totalEngagements) }}</div>
                                    <div>Follower: {{ number_format($followers) }}</div>
                                </div>
                            </x-nx-card>
                        </div>
                    </div>
                </div>
            </x-nx-section>
        @endif

        {{-- Hashtag Section --}}
        @if($topHashtags->count() > 0)
            <x-nx-section icon="heroicon-o-hashtag" title="Top Hashtags" :hint="$topHashtags->count() . ' Hashtags'"
                          description="Am häufigsten verwendete Hashtags in deinen Posts">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($topHashtags as $hashtag)
                        <x-nx-card class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-[7px] bg-[color:var(--nx-accent-soft)] font-semibold text-[color:var(--nx-muted)]">
                                    #
                                </div>
                                <div>
                                    <p class="font-medium text-[color:var(--nx-text)]">{{ $hashtag['name'] }}</p>
                                    <p class="text-xs text-[color:var(--nx-faint)]">
                                        {{ $hashtag['usage_count'] > 1 ? $hashtag['usage_count'] . ' Posts' : '1 Post' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Usage Indicator --}}
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-24 rounded-full bg-[color:var(--nx-hover)]">
                                    @php
                                        $maxCount = $topHashtags->max('usage_count');
                                        $percentage = ($hashtag['usage_count'] / $maxCount) * 100;
                                    @endphp
                                    <div class="h-2 rounded-full bg-[color:var(--nx-accent)]"
                                         style="width: {{ $percentage }}%">
                                    </div>
                                </div>
                            </div>
                        </x-nx-card>
                    @endforeach
                </div>
            </x-nx-section>
        @endif

        {{-- Media Grid --}}
        <x-nx-section x-data="{ viewMode: 'grid' }" icon="heroicon-o-photo" title="Media" :hint="$media->count() . ' Posts'" description="Alle Instagram Posts">
            <x-slot name="action">
                <x-nx-button variant="ghost" size="sm" icon @click="viewMode = viewMode === 'grid' ? 'list' : 'grid'">
                    @svg('heroicon-o-squares-2x2', 'w-5 h-5', ['x-show' => 'viewMode === "list"'])
                    @svg('heroicon-o-bars-4', 'w-5 h-5', ['x-show' => 'viewMode === "grid"'])
                </x-nx-button>
            </x-slot>

            {{-- Grid View --}}
            <div x-show="viewMode === 'grid'"
                 class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($media as $mediaItem)
                    <x-nx-card flush class="group overflow-hidden transition-colors hover:bg-[color:var(--nx-hover)]">
                        {{-- Post Header --}}
                        <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] p-2">
                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)]">
                                @svg('heroicon-o-camera', 'w-3 h-3 text-[color:var(--nx-muted)]')
                            </div>
                            <div class="flex items-center gap-2">
                                <p class="truncate text-xs font-medium text-[color:var(--nx-text)]">
                                    {{ $instagramAccount->username }}
                                </p>
                                @if($mediaItem->contextFiles->count() > 1)
                                    <div class="flex items-center gap-1 text-[color:var(--nx-faint)]">
                                        @svg('heroicon-o-squares-2x2', 'w-3 h-3')
                                        <span class="text-xs">{{ $mediaItem->contextFiles->count() }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Media Content --}}
                        <div class="relative bg-[color:var(--nx-hover)] aspect-square overflow-hidden">
                            @if($mediaItem->media_type === 'CAROUSEL_ALBUM' && $mediaItem->contextFiles->where('meta.role', 'carousel')->count() > 0)
                                {{-- Carousel Album --}}
                                @php
                                    $carouselItems = $mediaItem->contextFiles->where('meta.role', 'carousel')->sortBy(function($file) {
                                        return $file->meta['carousel_index'] ?? 999;
                                    });
                                @endphp
                                <div x-data="{ activeIndex: 0 }" class="absolute inset-0 h-full w-full">
                                    @foreach($carouselItems as $index => $carouselFile)
                                        <div @if($index === 0) x-show.immediate="activeIndex === {{ $index }}" @else x-show="activeIndex === {{ $index }}" @endif
                                             x-transition:enter="transition ease-out duration-300"
                                             x-transition:enter-start="opacity-0"
                                             x-transition:enter-end="opacity-100"
                                             x-transition:leave="transition ease-in duration-300"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             class="absolute inset-0 h-full w-full">
                                            @if($carouselFile->isImage())
                                                <img src="{{ route('core.context-files.show', ['token' => $carouselFile->token]) }}"
                                                     alt="Instagram Carousel Image {{ $index + 1 }}"
                                                     class="h-full w-full object-cover">
                                            @else
                                                <video class="h-full w-full object-cover"
                                                       playsinline autoplay muted loop preload="auto">
                                                    <source src="{{ route('core.context-files.show', ['token' => $carouselFile->token]) }}" type="{{ $carouselFile->mime_type }}">
                                                </video>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if($carouselItems->count() > 1)
                                        {{-- Navigation Arrows --}}
                                        <button @click="activeIndex = activeIndex === 0 ? {{ $carouselItems->count() - 1 }} : activeIndex - 1"
                                                class="absolute left-1 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-1 transition-colors opacity-0 group-hover:opacity-100 z-10">
                                            @svg('heroicon-o-chevron-left', 'w-4 h-4')
                                        </button>
                                        <button @click="activeIndex = activeIndex === {{ $carouselItems->count() - 1 }} ? 0 : activeIndex + 1"
                                                class="absolute right-1 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-1 transition-colors opacity-0 group-hover:opacity-100 z-10">
                                            @svg('heroicon-o-chevron-right', 'w-4 h-4')
                                        </button>

                                        {{-- Dots Indicator --}}
                                        <div class="absolute bottom-1 left-1/2 -translate-x-1/2 flex gap-1 z-10">
                                            @foreach($carouselItems as $index => $item)
                                                <button @click="activeIndex = {{ $index }}"
                                                        class="w-1 h-1 rounded-full transition-all"
                                                        :class="activeIndex === {{ $index }} ? 'bg-white w-2' : 'bg-white/50'">
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @elseif($mediaItem->media_type === 'VIDEO')
                                {{-- Video --}}
                                @if($mediaItem->contextFiles->where('meta.role', 'primary')->first())
                                    @php $primaryFile = $mediaItem->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                        <video class="absolute inset-0 h-full w-full object-cover"
                                           playsinline autoplay muted loop preload="auto">
                                        <source src="{{ route('core.context-files.show', ['token' => $primaryFile->token]) }}" type="{{ $primaryFile->mime_type }}">
                                        </video>
                                    @endif
                            @elseif($mediaItem->media_type === 'IMAGE' || !$mediaItem->media_type)
                                {{-- Single Image --}}
                                @if($mediaItem->contextFiles->where('meta.role', 'primary')->first())
                                    @php $primaryFile = $mediaItem->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                    <img src="{{ route('core.context-files.show', ['token' => $primaryFile->token]) }}"
                                         alt="Instagram Image"
                                         class="absolute inset-0 h-full w-full object-cover">
                                @endif
                                @endif
                            </div>

                            {{-- Post Stats --}}
                            <div class="border-t border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
                                <div class="px-3 py-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                            <span class="text-sm font-medium">{{ number_format($mediaItem->like_count) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                            <span class="text-sm font-medium text-[color:var(--nx-text)]">{{ number_format($mediaItem->comments_count) }}</span>
                                        </div>
                                    </div>
                                    @if($mediaItem->timestamp)
                                        <span class="text-xs text-[color:var(--nx-faint)]">{{ $mediaItem->timestamp->format('d.m.Y') }}</span>
                                    @endif
                                </div>
                                @if($mediaItem->caption)
                                    <div class="px-3 py-2 border-t border-[color:var(--nx-line)]">
                                        <p class="text-sm text-[color:var(--nx-text)] whitespace-pre-line line-clamp-2">{{ $mediaItem->caption }}</p>
                                    </div>
                                @endif
                        </div>
                    </x-nx-card>
                @endforeach
            </div>

            {{-- List View --}}
            <div x-show="viewMode === 'list'" class="space-y-4">
                @foreach($media as $mediaItem)
                    <x-nx-card flush class="group overflow-hidden">
                        <div class="grid grid-cols-3">
                            {{-- Media Preview --}}
                            <div class="relative bg-[color:var(--nx-hover)] aspect-square overflow-hidden">
                                @if($mediaItem->media_type === 'CAROUSEL_ALBUM' && $mediaItem->contextFiles->where('meta.role', 'carousel')->count() > 0)
                                    {{-- Carousel Album --}}
                                    @php
                                        $carouselItems = $mediaItem->contextFiles->where('meta.role', 'carousel')->sortBy(function($file) {
                                            return $file->meta['carousel_index'] ?? 999;
                                        });
                                    @endphp
                                    <div x-data="{ activeIndex: 0 }" class="absolute inset-0 h-full w-full">
                                        @foreach($carouselItems as $index => $carouselFile)
                                            <div x-show="activeIndex === {{ $index }}"
                                                 x-transition:enter="transition ease-out duration-300"
                                                 x-transition:enter-start="opacity-0"
                                                 x-transition:enter-end="opacity-100"
                                                 x-transition:leave="transition ease-in duration-300"
                                                 x-transition:leave-start="opacity-100"
                                                 x-transition:leave-end="opacity-0"
                                                 x-cloak
                                                 class="absolute inset-0 h-full w-full"
                                                 style="display: {{ $index === 0 ? 'block' : 'none' }};">
                                                @if($carouselFile->isImage())
                                                    <img src="{{ route('core.context-files.show', ['token' => $carouselFile->token]) }}"
                                                         alt="Instagram Carousel Image {{ $index + 1 }}"
                                                         class="h-full w-full object-cover">
                                                @else
                                                <video class="h-full w-full object-cover"
                                                       playsinline autoplay muted loop preload="auto">
                                                    <source src="{{ route('core.context-files.show', ['token' => $carouselFile->token]) }}" type="{{ $carouselFile->mime_type }}">
                                                </video>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if($carouselItems->count() > 1)
                                            {{-- Navigation Arrows --}}
                                            <button @click="activeIndex = activeIndex === 0 ? {{ $carouselItems->count() - 1 }} : activeIndex - 1"
                                                    class="absolute left-1 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-1 transition-colors opacity-0 group-hover:opacity-100 z-10">
                                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                                            </button>
                                            <button @click="activeIndex = activeIndex === {{ $carouselItems->count() - 1 }} ? 0 : activeIndex + 1"
                                                    class="absolute right-1 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-1 transition-colors opacity-0 group-hover:opacity-100 z-10">
                                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                                            </button>

                                            {{-- Dots Indicator --}}
                                            <div class="absolute bottom-1 left-1/2 -translate-x-1/2 flex gap-1 z-10">
                                                @foreach($carouselItems as $index => $item)
                                                    <button @click="activeIndex = {{ $index }}"
                                                            class="w-1 h-1 rounded-full transition-all"
                                                            :class="activeIndex === {{ $index }} ? 'bg-white w-2' : 'bg-white/50'">
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @elseif($mediaItem->media_type === 'VIDEO')
                                    {{-- Video --}}
                                    @if($mediaItem->contextFiles->where('meta.role', 'primary')->first())
                                        @php $primaryFile = $mediaItem->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                    <video class="absolute inset-0 h-full w-full object-cover"
                                           playsinline autoplay muted loop preload="auto">
                                        <source src="{{ route('core.context-files.show', ['token' => $primaryFile->token]) }}" type="{{ $primaryFile->mime_type }}">
                                    </video>
                                        @endif
                                @elseif($mediaItem->media_type === 'IMAGE' || !$mediaItem->media_type)
                                    {{-- Single Image --}}
                                    @if($mediaItem->contextFiles->where('meta.role', 'primary')->first())
                                        @php $primaryFile = $mediaItem->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                        <img src="{{ route('core.context-files.show', ['token' => $primaryFile->token]) }}"
                                             alt="Instagram Image"
                                             class="absolute inset-0 h-full w-full object-cover">
                                    @endif
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="col-span-2 p-4">
                                {{-- Header --}}
                                <div class="mb-3 flex items-center gap-2">
                                    <div class="flex h-4 w-4 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)]">
                                        @svg('heroicon-o-camera', 'w-3 h-3 text-[color:var(--nx-muted)]')
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-xs font-medium text-[color:var(--nx-text)]">
                                            {{ $instagramAccount->username }}
                                        </p>
                                        @if($mediaItem->contextFiles->count() > 1)
                                            <div class="flex items-center gap-1 text-[color:var(--nx-faint)]">
                                                @svg('heroicon-o-squares-2x2', 'w-3 h-3')
                                                <span class="text-xs">{{ $mediaItem->contextFiles->count() }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Caption --}}
                                @if($mediaItem->caption)
                                    <p class="mb-4 line-clamp-3 whitespace-pre-line text-sm text-[color:var(--nx-text)]">{{ $mediaItem->caption }}</p>
                                @endif

                                {{-- Stats --}}
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                        <span class="text-sm font-medium">{{ number_format($mediaItem->like_count) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                        <span class="text-sm font-medium text-[color:var(--nx-text)]">{{ number_format($mediaItem->comments_count) }}</span>
                                    </div>
                                    @if($mediaItem->timestamp)
                                        <span class="text-xs text-[color:var(--nx-faint)]">{{ $mediaItem->timestamp->format('d.m.Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-nx-card>
                @endforeach
            </div>
        </x-nx-section>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Instagram Account Details" width="w-80" :defaultOpen="true">
            <div class="p-5 space-y-5">
                {{-- Details --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Details</h3>
                    <div class="overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] divide-y divide-[color:var(--nx-line)]">
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Username</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $instagramAccount->username }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">External ID</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $instagramAccount->external_id }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Erstellt</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $instagramAccount->created_at->format('d.m.Y') }}</span>
                        </div>
                        @if($instagramAccount->brand)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Marke</span>
                                <a href="{{ route('brands.brands.show', $instagramAccount->brand) }}" class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-info)] hover:underline">{{ $instagramAccount->brand->name }}</a>
                            </div>
                        @endif
                        @if($instagramAccount->facebookPage)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Facebook Page</span>
                                <a href="{{ route('brands.facebook-pages.show', $instagramAccount->facebookPage) }}" class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-info)] hover:underline">{{ $instagramAccount->facebookPage->name }}</a>
                            </div>
                        @endif
                        @if($media->count() > 0)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Posts</span>
                                <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $media->count() }}</span>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </x-ui-page-sidebar>
    </x-slot>

</x-ui-page>
