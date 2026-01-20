<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="'@' . $instagramAccount->username" icon="heroicon-o-camera" />
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Hero Section --}}
        <div class="border-b border-[var(--ui-border)]/60 bg-white">
            <div class="grid lg:grid-cols-2 gap-12 items-start">
                {{-- Linke Spalte: Profilinfo --}}
                <div class="space-y-8">
                    <div class="flex items-start gap-8">
                        {{-- Profilbild --}}
                        <div class="relative group">
                            <div class="w-32 h-32 rounded-full ring-4 ring-white shadow-xl bg-[var(--ui-primary-5)] flex items-center justify-center">
                                @svg('heroicon-o-camera', 'w-16 h-16 text-[var(--ui-primary)]')
                            </div>
                        </div>

                        <div class="flex-1">
                            {{-- Username und Verifizierung --}}
                            <div class="flex items-center gap-2 mb-1">
                                <h1 class="text-xl font-semibold text-[var(--ui-secondary)]">{{ '@' . $instagramAccount->username }}</h1>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    @svg('heroicon-o-check-badge', 'w-3.5 h-3.5 mr-1')
                                    Verifiziert
                                </span>
                            </div>

                            {{-- Name und Bio --}}
                            <div class="mt-4 space-y-2">
                                @if($latestInsights && $latestInsights->current_name)
                                    <h2 class="text-base font-semibold text-[var(--ui-secondary)]">{{ $latestInsights->current_name }}</h2>
                                @endif
                                @if($latestInsights && $latestInsights->current_biography)
                                    <p class="text-[var(--ui-muted)] whitespace-pre-line text-sm leading-relaxed">
                                        {{ $latestInsights->current_biography }}
                                    </p>
                                @elseif($instagramAccount->description)
                                    <p class="text-[var(--ui-muted)] whitespace-pre-line text-sm leading-relaxed">
                                        {{ $instagramAccount->description }}
                                    </p>
                                @endif
                            </div>

                            {{-- Action Button --}}
                            <div class="mt-6">
                                <a href="https://instagram.com/{{ $instagramAccount->username }}" 
                                   target="_blank"
                                   class="inline-flex items-center justify-center px-6 py-2 border border-[var(--ui-border)]/60 rounded-lg text-sm font-medium text-[var(--ui-secondary)] bg-white hover:bg-[var(--ui-muted-5)] transition-colors duration-200">
                                    @svg('heroicon-o-arrow-top-right-on-square', 'w-4 h-4 mr-2')
                                    Auf Instagram ansehen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rechte Spalte: Statistiken --}}
                <div class="grid grid-cols-2 gap-4">
                    {{-- Follower --}}
                    <div class="p-6 rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:border-pink-200 transition-all duration-200 bg-gradient-to-br from-white to-pink-50/30">
                        <div class="flex items-center gap-4">
                            <div class="size-12 bg-pink-50 rounded-lg flex items-center justify-center">
                                @svg('heroicon-o-users', 'size-6 text-pink-600')
                            </div>
                            <div>
                                <p class="text-sm font-medium text-[var(--ui-muted)]">Follower</p>
                                <p class="text-2xl font-bold text-[var(--ui-secondary)]">
                                    {{ $latestInsights ? number_format($latestInsights->current_followers ?? $latestInsights->follower_count ?? 0) : '0' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Following --}}
                    <div class="p-6 rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:border-purple-200 transition-all duration-200 bg-gradient-to-br from-white to-purple-50/30">
                        <div class="flex items-center gap-4">
                            <div class="size-12 bg-purple-50 rounded-lg flex items-center justify-center">
                                @svg('heroicon-o-user-group', 'size-6 text-purple-600')
                            </div>
                            <div>
                                <p class="text-sm font-medium text-[var(--ui-muted)]">Following</p>
                                <p class="text-2xl font-bold text-[var(--ui-secondary)]">
                                    {{ $latestInsights ? number_format($latestInsights->current_follows ?? 0) : '0' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Likes --}}
                    <div class="p-6 rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:border-red-200 transition-all duration-200 bg-gradient-to-br from-white to-red-50/30">
                        <div class="flex items-center gap-4">
                            <div class="size-12 bg-red-50 rounded-lg flex items-center justify-center">
                                @svg('heroicon-o-heart', 'size-6 text-red-600')
                            </div>
                            <div>
                                <p class="text-sm font-medium text-[var(--ui-muted)]">Likes</p>
                                <p class="text-2xl font-bold text-[var(--ui-secondary)]">
                                    {{ number_format($media->sum('like_count')) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Kommentare --}}
                    <div class="p-6 rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:border-blue-200 transition-all duration-200 bg-gradient-to-br from-white to-blue-50/30">
                        <div class="flex items-center gap-4">
                            <div class="size-12 bg-blue-50 rounded-lg flex items-center justify-center">
                                @svg('heroicon-o-chat-bubble-left', 'size-6 text-blue-600')
                            </div>
                            <div>
                                <p class="text-sm font-medium text-[var(--ui-muted)]">Kommentare</p>
                                <p class="text-2xl font-bold text-[var(--ui-secondary)]">
                                    {{ number_format($media->sum('comments_count')) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Posts --}}
                    <div class="p-6 rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:border-amber-200 transition-all duration-200 bg-gradient-to-br from-white to-amber-50/30 col-span-2">
                        <div class="flex items-center gap-4">
                            <div class="size-12 bg-amber-50 rounded-lg flex items-center justify-center">
                                @svg('heroicon-o-photo', 'size-6 text-amber-600')
                            </div>
                            <div>
                                <p class="text-sm font-medium text-[var(--ui-muted)]">Gesamt Posts</p>
                                <p class="text-2xl font-bold text-[var(--ui-secondary)]">
                                    {{ number_format($media->count()) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Latest Post Performance --}}
        @if($lastPost = $media->first())
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-6">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-photo', 'w-6 h-6 text-[var(--ui-muted)]')
                            <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Letzter Post</h2>
                        </div>
                        <p class="text-[var(--ui-muted)] text-sm">
                            Veröffentlicht: {{ $lastPost->timestamp ? $lastPost->timestamp->format('d.m.Y, H:i') : 'Unbekannt' }} Uhr
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    {{-- Post Preview --}}
                    <div class="border border-[var(--ui-border)]/60 rounded-lg overflow-hidden">
                        {{-- Post Header --}}
                        <div class="p-2 flex items-center gap-2 border-b border-[var(--ui-border)]/60">
                            <div class="w-4 h-4 rounded-full bg-[var(--ui-primary-5)] flex items-center justify-center">
                                @svg('heroicon-o-camera', 'w-3 h-3 text-[var(--ui-primary)]')
                            </div>
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-medium text-[var(--ui-secondary)] truncate">
                                    {{ $instagramAccount->username }}
                                </p>
                                @if($lastPost->contextFiles->count() > 1)
                                    <div class="flex items-center gap-1 text-[var(--ui-muted)]">
                                        @svg('heroicon-o-squares-2x2', 'w-3 h-3')
                                        <span class="text-xs">{{ $lastPost->contextFiles->count() }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Media Content --}}
                        <div class="relative bg-[var(--ui-muted-5)]">
                            <div class="aspect-w-1 aspect-h-1">
                                @if($lastPost->media_type === 'CAROUSEL_ALBUM' && $lastPost->contextFiles->where('meta.role', 'carousel')->count() > 0)
                                    {{-- Carousel Album --}}
                                    @php 
                                        $carouselItems = $lastPost->contextFiles->where('meta.role', 'carousel')->sortBy(function($file) {
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
                                                 class="absolute inset-0 h-full w-full">
                                                @if($carouselFile->isImage())
                                                    <img src="{{ $carouselFile->url }}"
                                                         alt="Instagram Carousel Image {{ $index + 1 }}"
                                                         class="h-full w-full object-contain">
                                                @else
                                                    <video class="h-full w-full object-contain"
                                                           playsinline controls>
                                                        <source src="{{ $carouselFile->url }}" type="{{ $carouselFile->mime_type }}">
                                                    </video>
                                                @endif
                                            </div>
                                        @endforeach
                                        
                                        @if($carouselItems->count() > 1)
                                            {{-- Navigation Arrows --}}
                                            <button @click="activeIndex = activeIndex > 0 ? activeIndex - 1 : {{ $carouselItems->count() - 1 }}"
                                                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 transition-colors">
                                                @svg('heroicon-o-chevron-left', 'w-5 h-5')
                                            </button>
                                            <button @click="activeIndex = activeIndex < {{ $carouselItems->count() - 1 }} ? activeIndex + 1 : 0"
                                                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 transition-colors">
                                                @svg('heroicon-o-chevron-right', 'w-5 h-5')
                                            </button>
                                            
                                            {{-- Dots Indicator --}}
                                            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5">
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
                                        <video class="absolute inset-0 h-full w-full object-contain"
                                               playsinline controls>
                                            <source src="{{ $primaryFile->url }}" type="{{ $primaryFile->mime_type }}">
                                        </video>
                                    @elseif($lastPost->media_url)
                                        <video class="absolute inset-0 h-full w-full object-contain"
                                               playsinline controls>
                                            <source src="{{ $lastPost->media_url }}" type="video/mp4">
                                        </video>
                                    @endif
                                @elseif($lastPost->media_type === 'IMAGE')
                                    {{-- Single Image --}}
                                    @if($lastPost->contextFiles->where('meta.role', 'primary')->first())
                                        @php $primaryFile = $lastPost->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                        <img src="{{ $primaryFile->url }}"
                                             alt="Instagram Image"
                                             class="absolute inset-0 h-full w-full object-contain">
                                    @elseif($lastPost->media_url)
                                        <img src="{{ $lastPost->media_url }}"
                                             alt="Instagram Image"
                                             class="absolute inset-0 h-full w-full object-contain">
                                    @endif
                                @elseif($lastPost->contextFiles->where('meta.role', 'primary')->first())
                                    {{-- Fallback: Primary File --}}
                                    @php $primaryFile = $lastPost->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                    @if($primaryFile->isImage())
                                        <img src="{{ $primaryFile->url }}"
                                             alt="Instagram Image"
                                             class="absolute inset-0 h-full w-full object-contain">
                                    @else
                                        <video class="absolute inset-0 h-full w-full object-contain"
                                               playsinline controls>
                                            <source src="{{ $primaryFile->url }}" type="{{ $primaryFile->mime_type }}">
                                        </video>
                                    @endif
                                @elseif($lastPost->media_url)
                                    <img src="{{ $lastPost->media_url }}"
                                         alt="Instagram Image"
                                         class="absolute inset-0 h-full w-full object-contain">
                                @endif
                            </div>

                            {{-- Post Stats --}}
                            <div class="border-t border-[var(--ui-border)]/60 bg-white">
                                <div class="px-3 py-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                            <span class="text-sm font-medium">{{ number_format($lastPost->like_count) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-[var(--ui-muted)]')
                                            <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ number_format($lastPost->comments_count) }}</span>
                                        </div>
                                    </div>
                                    @if($lastPost->timestamp)
                                        <span class="text-xs text-[var(--ui-muted)]">{{ $lastPost->timestamp->format('d.m.Y') }}</span>
                                    @endif
                                </div>
                                @if($lastPost->caption)
                                    <div class="px-3 py-2 border-t border-[var(--ui-border)]/40">
                                        <p class="text-sm text-[var(--ui-secondary)] whitespace-pre-line line-clamp-2">{{ $lastPost->caption }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Post Stats --}}
                    <div class="space-y-6">
                        {{-- Quick Stats --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-white rounded-lg border border-[var(--ui-border)]/60">
                                <div class="flex items-center gap-2 text-pink-600 mb-2">
                                    @svg('heroicon-o-heart', 'w-5 h-5')
                                    <span class="text-lg font-bold">{{ number_format($lastPost->like_count) }}</span>
                                </div>
                                <span class="text-sm text-[var(--ui-muted)]">Likes</span>
                            </div>
                            
                            <div class="p-4 bg-white rounded-lg border border-[var(--ui-border)]/60">
                                <div class="flex items-center gap-2 text-blue-600 mb-2">
                                    @svg('heroicon-o-chat-bubble-left', 'w-5 h-5')
                                    <span class="text-lg font-bold">{{ number_format($lastPost->comments_count) }}</span>
                                </div>
                                <span class="text-sm text-[var(--ui-muted)]">Kommentare</span>
                            </div>

                            {{-- Insights Stats --}}
                            @if($lastPost->latestInsight)
                                <div class="p-4 bg-white rounded-lg border border-[var(--ui-border)]/60">
                                    <div class="flex items-center gap-2 text-amber-600 mb-2">
                                        @svg('heroicon-o-bookmark', 'w-5 h-5')
                                        <span class="text-lg font-bold">{{ number_format($lastPost->latestInsight->saved ?? 0) }}</span>
                                    </div>
                                    <span class="text-sm text-[var(--ui-muted)]">Gespeichert</span>
                                </div>

                                <div class="p-4 bg-white rounded-lg border border-[var(--ui-border)]/60">
                                    <div class="flex items-center gap-2 text-green-600 mb-2">
                                        @svg('heroicon-o-share', 'w-5 h-5')
                                        <span class="text-lg font-bold">{{ number_format($lastPost->latestInsight->shares ?? 0) }}</span>
                                    </div>
                                    <span class="text-sm text-[var(--ui-muted)]">Geteilt</span>
                                </div>
                            @endif
                        </div>

                        {{-- Performance Metrics --}}
                        @if($lastPost->latestInsight)
                            <div class="bg-[var(--ui-muted-5)] rounded-lg p-4 space-y-4">
                                <h4 class="font-medium text-[var(--ui-secondary)]">Performance</h4>
                                <div class="grid gap-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            @svg('heroicon-o-eye', 'w-4 h-4 text-[var(--ui-muted)]')
                                            <span class="text-[var(--ui-muted)]">Reichweite</span>
                                        </div>
                                        <span class="font-medium text-[var(--ui-secondary)]">{{ number_format($lastPost->latestInsight->reach ?? 0) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            @svg('heroicon-o-cursor-arrow-rays', 'w-4 h-4 text-[var(--ui-muted)]')
                                            <span class="text-[var(--ui-muted)]">Impressionen</span>
                                        </div>
                                        <span class="font-medium text-[var(--ui-secondary)]">{{ number_format($lastPost->latestInsight->impressions ?? 0) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            @svg('heroicon-o-hand-raised', 'w-4 h-4 text-[var(--ui-muted)]')
                                            <span class="text-[var(--ui-muted)]">Interaktionen</span>
                                        </div>
                                        <span class="font-medium text-[var(--ui-secondary)]">{{ number_format($lastPost->latestInsight->total_interactions ?? 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Post Details --}}
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-4 space-y-3">
                            <h4 class="font-medium text-[var(--ui-secondary)]">Post Details</h4>
                            <div class="grid gap-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-[var(--ui-muted)]">Typ</span>
                                    <span class="font-medium text-[var(--ui-secondary)]">{{ $lastPost->media_type }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[var(--ui-muted)]">Story</span>
                                    <span class="font-medium text-[var(--ui-secondary)]">{{ $lastPost->is_story ? 'Ja' : 'Nein' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[var(--ui-muted)]">Medien</span>
                                    <span class="font-medium text-[var(--ui-secondary)]">{{ $lastPost->contextFiles->count() }}</span>
                                </div>
                                @if($lastPost->permalink)
                                    <a href="{{ $lastPost->permalink }}" 
                                       target="_blank"
                                       class="inline-flex items-center justify-center px-4 py-2 mt-2 border border-transparent rounded-lg text-sm font-medium text-white bg-pink-600 hover:bg-pink-700">
                                        @svg('heroicon-o-arrow-top-right-on-square', 'w-4 h-4 mr-2')
                                        Auf Instagram ansehen
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Performance Insights --}}
        @if($latestInsights)
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-[var(--ui-border)]/60">
                    <h2 class="text-2xl font-bold text-[var(--ui-secondary)] flex items-center gap-2">
                        @svg('heroicon-o-chart-bar', 'w-6 h-6 text-[var(--ui-muted)]')
                        Account Performance
                    </h2>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-[var(--ui-muted)]">Letztes Update: {{ $latestInsights->updated_at->format('d.m.Y, H:i') }} Uhr</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Letzter Tag
                        </span>
                    </div>
                </div>

                {{-- Performance Grid --}}
                <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Reichweite & Sichtbarkeit --}}
                    <div class="lg:col-span-1">
                        <h3 class="text-base font-semibold text-[var(--ui-secondary)] flex items-center gap-2 mb-4">
                            @svg('heroicon-o-eye', 'w-5 h-5 text-blue-500')
                            Reichweite & Sichtbarkeit
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="bg-[var(--ui-muted-5)] rounded-xl p-4">
                                <div class="flex items-center justify-between h-full">
                                    <div>
                                        <h4 class="text-sm font-medium text-[var(--ui-muted)]">Reichweite</h4>
                                        <p class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">{{ number_format($latestInsights->reach ?? 0) }}</p>
                                    </div>
                                    <div class="flex items-center text-blue-600">
                                        @svg('heroicon-o-users', 'w-5 h-5')
                                        <span class="text-xs ml-1">Unique Accounts</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-[var(--ui-muted-5)] rounded-xl p-4">
                                <div class="flex items-center justify-between h-full">
                                    <div>
                                        <h4 class="text-sm font-medium text-[var(--ui-muted)]">Impressionen</h4>
                                        <p class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">{{ number_format($latestInsights->impressions ?? 0) }}</p>
                                    </div>
                                    <div class="flex items-center text-blue-600">
                                        @svg('heroicon-o-eye', 'w-5 h-5')
                                        <span class="text-xs ml-1">Gesamte Ansichten</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Engagement --}}
                    <div class="lg:col-span-1">
                        <h3 class="text-base font-semibold text-[var(--ui-secondary)] flex items-center gap-2 mb-4">
                            @svg('heroicon-o-heart', 'w-5 h-5 text-pink-500')
                            Engagement
                        </h3>

                        <div class="space-y-4">
                            <div class="bg-[var(--ui-muted-5)] rounded-xl p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 class="text-sm font-medium text-[var(--ui-muted)]">Interaktionen</h4>
                                        <p class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">{{ number_format($latestInsights->total_interactions ?? 0) }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                        <span class="text-sm text-[var(--ui-muted)]">{{ number_format($latestInsights->likes ?? 0) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-blue-500')
                                        <span class="text-sm text-[var(--ui-muted)]">{{ number_format($latestInsights->comments ?? 0) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-bookmark', 'w-4 h-4 text-amber-500')
                                        <span class="text-sm text-[var(--ui-muted)]">{{ number_format($latestInsights->saves ?? 0) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-share', 'w-4 h-4 text-green-500')
                                        <span class="text-sm text-[var(--ui-muted)]">{{ number_format($latestInsights->shares ?? 0) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-[var(--ui-muted-5)] rounded-xl p-4">
                                <div class="flex items-center justify-between h-full">
                                    <div>
                                        <h4 class="text-sm font-medium text-[var(--ui-muted)]">Profilaufrufe</h4>
                                        <p class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">{{ number_format($latestInsights->profile_views ?? 0) }}</p>
                                    </div>
                                    <div class="flex items-center text-[var(--ui-muted)]">
                                        @svg('heroicon-o-user', 'w-5 h-5')
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Engagement Rate --}}
                    <div class="lg:col-span-1">
                        <h3 class="text-base font-semibold text-[var(--ui-secondary)] flex items-center gap-2 mb-4">
                            @svg('heroicon-o-chart-bar', 'w-5 h-5 text-purple-500')
                            Engagement Rate
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="bg-[var(--ui-muted-5)] rounded-xl p-4">
                                <div class="flex items-center justify-between h-full">
                                    <div>
                                        <h4 class="text-sm font-medium text-[var(--ui-muted)]">Durchschnittlich</h4>
                                        @php
                                            $followers = $latestInsights->current_followers ?? $latestInsights->follower_count ?? 0;
                                            $totalEngagements = ($latestInsights->likes ?? 0) + ($latestInsights->comments ?? 0) + ($latestInsights->saves ?? 0) + ($latestInsights->shares ?? 0);
                                            $engagementRate = $followers > 0 ? ($totalEngagements / $followers) * 100 : 0;
                                        @endphp
                                        <p class="text-2xl font-bold text-[var(--ui-secondary)] mt-1">
                                            {{ number_format($engagementRate, 1) }}%
                                        </p>
                                    </div>
                                    <div class="flex items-center text-purple-600">
                                        @svg('heroicon-o-arrow-trending-up', 'w-5 h-5')
                                        <span class="text-xs ml-1">pro Follower</span>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-[var(--ui-muted)]">
                                    <div>Engagements: {{ number_format($totalEngagements) }}</div>
                                    <div>Follower: {{ number_format($followers) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Hashtag Section --}}
        @if($topHashtags->count() > 0)
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-6">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-hashtag', 'w-6 h-6 text-[var(--ui-muted)]')
                            <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Top Hashtags</h2>
                        </div>
                        <p class="text-[var(--ui-muted)] text-sm">Am häufigsten verwendete Hashtags in deinen Posts</p>
                    </div>
                    <span class="px-4 py-2 bg-[var(--ui-muted-5)] text-[var(--ui-secondary)] rounded-full text-sm font-medium">
                        {{ $topHashtags->count() }} Hashtags
                    </span>
                </div>

                {{-- Hashtag Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($topHashtags as $hashtag)
                        <div class="flex items-center justify-between p-4 bg-gradient-to-br from-white to-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60 hover:border-purple-200 transition-all duration-200">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-purple-50 text-purple-600 font-semibold">
                                    #
                                </div>
                                <div>
                                    <p class="font-medium text-[var(--ui-secondary)]">{{ $hashtag['name'] }}</p>
                                    <p class="text-xs text-[var(--ui-muted)]">
                                        {{ $hashtag['usage_count'] > 1 ? $hashtag['usage_count'] . ' Posts' : '1 Post' }}
                                    </p>
                                </div>
                            </div>
                            
                            {{-- Usage Indicator --}}
                            <div class="flex items-center gap-2">
                                <div class="w-24 bg-[var(--ui-muted-5)] rounded-full h-2">
                                    @php
                                        $maxCount = $topHashtags->max('usage_count');
                                        $percentage = ($hashtag['usage_count'] / $maxCount) * 100;
                                    @endphp
                                    <div class="bg-purple-500 h-2 rounded-full" 
                                         style="width: {{ $percentage }}%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Media Grid --}}
        <div x-data="{ viewMode: 'grid' }" class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Media</h2>
                    <p class="text-[var(--ui-muted)] text-sm">Alle Instagram Posts</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 bg-pink-100 text-pink-700 rounded-full text-sm font-medium">
                        {{ $media->count() }} Posts
                    </span>
                    <button @click="viewMode = viewMode === 'grid' ? 'list' : 'grid'" 
                            class="p-2 rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-[var(--ui-primary-5)] transition-colors duration-200">
                        @svg('heroicon-o-squares-2x2', 'w-5 h-5', ['x-show' => 'viewMode === "list"'])
                        @svg('heroicon-o-bars-4', 'w-5 h-5', ['x-show' => 'viewMode === "grid"'])
                    </button>
                </div>
            </div>

            {{-- Grid View --}}
            <div x-show="viewMode === 'grid'" 
                 class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($media as $mediaItem)
                    <div class="group border border-[var(--ui-border)]/60 rounded-lg overflow-hidden hover:border-[var(--ui-primary)]/60 transition-colors">
                        {{-- Post Header --}}
                        <div class="p-2 flex items-center gap-2 border-b border-[var(--ui-border)]/60">
                            <div class="w-4 h-4 rounded-full bg-[var(--ui-primary-5)] flex items-center justify-center">
                                @svg('heroicon-o-camera', 'w-3 h-3 text-[var(--ui-primary)]')
                            </div>
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-medium text-[var(--ui-secondary)] truncate">
                                    {{ $instagramAccount->username }}
                                </p>
                                @if($mediaItem->contextFiles->count() > 1)
                                    <div class="flex items-center gap-1 text-[var(--ui-muted)]">
                                        @svg('heroicon-o-squares-2x2', 'w-3 h-3')
                                        <span class="text-xs">{{ $mediaItem->contextFiles->count() }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Media Content --}}
                        <div class="relative bg-[var(--ui-muted-5)]">
                            <div class="aspect-w-1 aspect-h-1">
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
                                                 class="absolute inset-0 h-full w-full">
                                                @if($carouselFile->isImage())
                                                    <img src="{{ $carouselFile->url }}"
                                                         alt="Instagram Carousel Image {{ $index + 1 }}"
                                                         class="h-full w-full object-contain">
                                                @else
                                                    <video class="h-full w-full object-contain"
                                                           playsinline muted loop>
                                                        <source src="{{ $carouselFile->url }}" type="{{ $carouselFile->mime_type }}">
                                                    </video>
                                                @endif
                                            </div>
                                        @endforeach
                                        
                                        @if($carouselItems->count() > 1)
                                            {{-- Navigation Arrows --}}
                                            <button @click="activeIndex = activeIndex > 0 ? activeIndex - 1 : {{ $carouselItems->count() - 1 }}"
                                                    class="absolute left-1 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-1 transition-colors opacity-0 group-hover:opacity-100">
                                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                                            </button>
                                            <button @click="activeIndex = activeIndex < {{ $carouselItems->count() - 1 }} ? activeIndex + 1 : 0"
                                                    class="absolute right-1 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-1 transition-colors opacity-0 group-hover:opacity-100">
                                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                                            </button>
                                            
                                            {{-- Dots Indicator --}}
                                            <div class="absolute bottom-1 left-1/2 -translate-x-1/2 flex gap-1">
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
                                        <video class="absolute inset-0 h-full w-full object-contain"
                                               playsinline muted loop>
                                            <source src="{{ $primaryFile->url }}" type="{{ $primaryFile->mime_type }}">
                                        </video>
                                    @elseif($mediaItem->media_url)
                                        <video class="absolute inset-0 h-full w-full object-contain"
                                               playsinline muted loop>
                                            <source src="{{ $mediaItem->media_url }}" type="video/mp4">
                                        </video>
                                    @endif
                                @elseif($mediaItem->media_type === 'IMAGE')
                                    {{-- Single Image --}}
                                    @if($mediaItem->contextFiles->where('meta.role', 'primary')->first())
                                        @php $primaryFile = $mediaItem->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                        <img src="{{ $primaryFile->url }}"
                                             alt="Instagram Image"
                                             class="absolute inset-0 h-full w-full object-contain">
                                    @elseif($mediaItem->media_url)
                                        <img src="{{ $mediaItem->media_url }}"
                                             alt="Instagram Image"
                                             class="absolute inset-0 h-full w-full object-contain">
                                    @endif
                                @elseif($mediaItem->contextFiles->where('meta.role', 'primary')->first())
                                    {{-- Fallback: Primary File --}}
                                    @php $primaryFile = $mediaItem->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                    @if($primaryFile->isImage())
                                        <img src="{{ $primaryFile->url }}"
                                             alt="Instagram Image"
                                             class="absolute inset-0 h-full w-full object-contain">
                                    @else
                                        <video class="absolute inset-0 h-full w-full object-contain"
                                               playsinline muted loop>
                                            <source src="{{ $primaryFile->url }}" type="{{ $primaryFile->mime_type }}">
                                        </video>
                                    @endif
                                @elseif($mediaItem->media_url)
                                    <img src="{{ $mediaItem->media_url }}"
                                         alt="Instagram Image"
                                         class="absolute inset-0 h-full w-full object-contain">
                                @endif
                            </div>

                            {{-- Post Stats --}}
                            <div class="border-t border-[var(--ui-border)]/60 bg-white">
                                <div class="px-3 py-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                            <span class="text-sm font-medium">{{ number_format($mediaItem->like_count) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-[var(--ui-muted)]')
                                            <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ number_format($mediaItem->comments_count) }}</span>
                                        </div>
                                    </div>
                                    @if($mediaItem->timestamp)
                                        <span class="text-xs text-[var(--ui-muted)]">{{ $mediaItem->timestamp->format('d.m.Y') }}</span>
                                    @endif
                                </div>
                                @if($mediaItem->caption)
                                    <div class="px-3 py-2 border-t border-[var(--ui-border)]/40">
                                        <p class="text-sm text-[var(--ui-secondary)] whitespace-pre-line line-clamp-2">{{ $mediaItem->caption }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- List View --}}
            <div x-show="viewMode === 'list'" class="space-y-4">
                @foreach($media as $mediaItem)
                    <div class="group border border-[var(--ui-border)]/60 rounded-lg overflow-hidden">
                        <div class="grid grid-cols-3">
                            {{-- Media Preview --}}
                            <div class="relative bg-[var(--ui-muted-5)]">
                                <div class="aspect-w-1 aspect-h-1">
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
                                                     class="absolute inset-0 h-full w-full">
                                                    @if($carouselFile->isImage())
                                                        <img src="{{ $carouselFile->url }}"
                                                             alt="Instagram Carousel Image {{ $index + 1 }}"
                                                             class="h-full w-full object-contain">
                                                    @else
                                                        <video class="h-full w-full object-contain"
                                                               playsinline muted loop>
                                                            <source src="{{ $carouselFile->url }}" type="{{ $carouselFile->mime_type }}">
                                                        </video>
                                                    @endif
                                                </div>
                                            @endforeach
                                            
                                            @if($carouselItems->count() > 1)
                                                {{-- Navigation Arrows --}}
                                                <button @click="activeIndex = activeIndex > 0 ? activeIndex - 1 : {{ $carouselItems->count() - 1 }}"
                                                        class="absolute left-1 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-1 transition-colors opacity-0 group-hover:opacity-100">
                                                    @svg('heroicon-o-chevron-left', 'w-4 h-4')
                                                </button>
                                                <button @click="activeIndex = activeIndex < {{ $carouselItems->count() - 1 }} ? activeIndex + 1 : 0"
                                                        class="absolute right-1 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full p-1 transition-colors opacity-0 group-hover:opacity-100">
                                                    @svg('heroicon-o-chevron-right', 'w-4 h-4')
                                                </button>
                                                
                                                {{-- Dots Indicator --}}
                                                <div class="absolute bottom-1 left-1/2 -translate-x-1/2 flex gap-1">
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
                                            <video class="absolute inset-0 h-full w-full object-contain"
                                                   playsinline muted loop>
                                                <source src="{{ $primaryFile->url }}" type="{{ $primaryFile->mime_type }}">
                                            </video>
                                        @elseif($mediaItem->media_url)
                                            <video class="absolute inset-0 h-full w-full object-contain"
                                                   playsinline muted loop>
                                                <source src="{{ $mediaItem->media_url }}" type="video/mp4">
                                            </video>
                                        @endif
                                    @elseif($mediaItem->media_type === 'IMAGE')
                                        {{-- Single Image --}}
                                        @if($mediaItem->contextFiles->where('meta.role', 'primary')->first())
                                            @php $primaryFile = $mediaItem->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                            <img src="{{ $primaryFile->url }}"
                                                 alt="Instagram Image"
                                                 class="absolute inset-0 h-full w-full object-contain">
                                        @elseif($mediaItem->media_url)
                                            <img src="{{ $mediaItem->media_url }}"
                                                 alt="Instagram Image"
                                                 class="absolute inset-0 h-full w-full object-contain">
                                        @endif
                                    @elseif($mediaItem->contextFiles->where('meta.role', 'primary')->first())
                                        {{-- Fallback: Primary File --}}
                                        @php $primaryFile = $mediaItem->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                        @if($primaryFile->isImage())
                                            <img src="{{ $primaryFile->url }}"
                                                 alt="Instagram Image"
                                                 class="absolute inset-0 h-full w-full object-contain">
                                        @else
                                            <video class="absolute inset-0 h-full w-full object-contain"
                                                   playsinline muted loop>
                                                <source src="{{ $primaryFile->url }}" type="{{ $primaryFile->mime_type }}">
                                            </video>
                                        @endif
                                    @elseif($mediaItem->media_url)
                                        <img src="{{ $mediaItem->media_url }}"
                                             alt="Instagram Image"
                                             class="absolute inset-0 h-full w-full object-contain">
                                    @endif
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="col-span-2 p-4">
                                {{-- Header --}}
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-4 h-4 rounded-full bg-[var(--ui-primary-5)] flex items-center justify-center">
                                        @svg('heroicon-o-camera', 'w-3 h-3 text-[var(--ui-primary)]')
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-medium text-[var(--ui-secondary)] truncate">
                                            {{ $instagramAccount->username }}
                                        </p>
                                        @if($mediaItem->contextFiles->count() > 1)
                                            <div class="flex items-center gap-1 text-[var(--ui-muted)]">
                                                @svg('heroicon-o-squares-2x2', 'w-3 h-3')
                                                <span class="text-xs">{{ $mediaItem->contextFiles->count() }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Caption --}}
                                @if($mediaItem->caption)
                                    <p class="text-sm text-[var(--ui-secondary)] whitespace-pre-line line-clamp-3 mb-4">{{ $mediaItem->caption }}</p>
                                @endif

                                {{-- Stats --}}
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                        <span class="text-sm font-medium">{{ number_format($mediaItem->like_count) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-[var(--ui-muted)]')
                                        <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ number_format($mediaItem->comments_count) }}</span>
                                    </div>
                                    @if($mediaItem->timestamp)
                                        <span class="text-xs text-[var(--ui-muted)]">{{ $mediaItem->timestamp->format('d.m.Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Instagram Account Details" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Username</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $instagramAccount->username }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">External ID</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $instagramAccount->external_id }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $instagramAccount->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($instagramAccount->brand)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Marke</span>
                                <a href="{{ route('brands.brands.show', $instagramAccount->brand) }}" class="text-sm text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $instagramAccount->brand->name }}
                                </a>
                            </div>
                        @endif
                        @if($instagramAccount->facebookPage)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Facebook Page</span>
                                <a href="{{ route('brands.facebook-pages.show', $instagramAccount->facebookPage) }}" class="text-sm text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $instagramAccount->facebookPage->name }}
                                </a>
                            </div>
                        @endif
                        @if($media->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Posts</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $media->count() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Aktionen --}}
                @can('update', $instagramAccount)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                        <div class="space-y-2">
                            <x-ui-button 
                                variant="primary" 
                                size="sm"
                                wire:click="syncMedia"
                                class="w-full"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-arrow-path', 'w-4 h-4')
                                    <span>Media synchronisieren</span>
                                </span>
                            </x-ui-button>
                        </div>
                    </div>
                @endcan
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
