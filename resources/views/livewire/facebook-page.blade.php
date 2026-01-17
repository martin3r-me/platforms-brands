<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$facebookPage->name" icon="heroicon-o-globe-alt" />
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Post Details Section --}}
        <section class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-information-circle', 'w-6 h-6 text-[var(--ui-muted)]')
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Aktueller Content</h2>
                    </div>
                    <p class="text-[var(--ui-muted)] text-sm">Übersicht der letzten Aktivitäten</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                @if($lastPost = $posts->first())
                    {{-- Instagram-Style Post Preview --}}
                    <div class="border border-[var(--ui-border)]/60 rounded-xl overflow-hidden w-full max-w-sm">
                        {{-- Post Header --}}
                        <div class="p-3 flex items-center gap-2 border-b border-[var(--ui-border)]/60">
                            <div class="w-6 h-6 rounded-full bg-[var(--ui-primary-5)] flex items-center justify-center">
                                @svg('heroicon-o-globe-alt', 'w-4 h-4 text-[var(--ui-primary)]')
                            </div>
                            <p class="text-sm font-medium text-[var(--ui-secondary)]">{{ $facebookPage->name }}</p>
                        </div>

                        {{-- Post Image --}}
                        @if($lastPost->contextFiles->where('meta.role', 'primary')->first())
                            @php $primaryFile = $lastPost->contextFiles->where('meta.role', 'primary')->first(); @endphp
                            <div class="aspect-square">
                                <img src="{{ $primaryFile->url }}" 
                                     alt="Facebook Post" 
                                     class="w-full h-full object-cover">
                            </div>
                        @elseif($lastPost->media_url)
                            <div class="aspect-square">
                                <img src="{{ $lastPost->media_url }}" 
                                     alt="Facebook Post" 
                                     class="w-full h-full object-cover">
                            </div>
                        @endif

                        {{-- Post Actions & Caption --}}
                        <div class="p-3 text-xs space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1">
                                    @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                    <span>{{ number_format($lastPost->like_count) }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-[var(--ui-muted)]')
                                    <span>{{ number_format($lastPost->comment_count) }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    @svg('heroicon-o-share', 'w-4 h-4 text-[var(--ui-muted)]')
                                    <span>{{ number_format($lastPost->share_count) }}</span>
                                </div>
                            </div>
                            @if($lastPost->message)
                                <p class="text-[var(--ui-secondary)] line-clamp-3">{{ $lastPost->message }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Additional Info --}}
                    <div class="space-y-6">
                        {{-- Recent Performance --}}
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-[var(--ui-secondary)]">Performance der letzten 7 Tage</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-white rounded-lg border border-[var(--ui-border)]/60">
                                    <div class="flex items-center gap-2 text-pink-600 mb-3">
                                        @svg('heroicon-o-heart', 'w-5 h-5')
                                        <span class="text-lg font-bold">{{ number_format($posts->sum('like_count')) }}</span>
                                    </div>
                                    <span class="text-sm text-[var(--ui-muted)]">Likes gesamt</span>
                                </div>
                                <div class="p-4 bg-white rounded-lg border border-[var(--ui-border)]/60">
                                    <div class="flex items-center gap-2 text-blue-600 mb-3">
                                        @svg('heroicon-o-chat-bubble-left', 'w-5 h-5')
                                        <span class="text-lg font-bold">{{ number_format($posts->sum('comment_count')) }}</span>
                                    </div>
                                    <span class="text-sm text-[var(--ui-muted)]">Kommentare gesamt</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="col-span-2 bg-[var(--ui-muted-5)] rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                            @svg('heroicon-o-information-circle', 'w-8 h-8 text-[var(--ui-muted)]')
                        </div>
                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Posts</h3>
                        <p class="text-sm text-[var(--ui-muted)]">Es wurden noch keine Facebook Posts synchronisiert.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- Latest Posts Section --}}
        <section x-data="{ viewMode: 'grid' }" class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-2">
                        @svg('heroicon-o-photo', 'w-6 h-6 text-[var(--ui-muted)]')
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Letzte Posts</h2>
                    </div>
                    <p class="text-[var(--ui-muted)] text-sm">Die neuesten Facebook Page Posts</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 bg-pink-100 text-pink-700 rounded-full text-sm font-medium">
                        {{ $posts->count() }} Posts
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
                @foreach($posts as $post)
                    <div class="border border-[var(--ui-border)]/60 rounded-lg overflow-hidden hover:border-[var(--ui-primary)]/60 transition-colors">
                        {{-- Post Header --}}
                        <div class="p-2 flex items-center gap-2 border-b border-[var(--ui-border)]/60">
                            <div class="w-4 h-4 rounded-full bg-[var(--ui-primary-5)] flex items-center justify-center">
                                @svg('heroicon-o-globe-alt', 'w-3 h-3 text-[var(--ui-primary)]')
                            </div>
                            <p class="text-xs font-medium text-[var(--ui-secondary)] truncate">{{ $facebookPage->name }}</p>
                        </div>

                        {{-- Post Image --}}
                        @if($post->contextFiles->where('meta.role', 'primary')->first())
                            @php $primaryFile = $post->contextFiles->where('meta.role', 'primary')->first(); @endphp
                            <div class="aspect-square">
                                <img src="{{ $primaryFile->thumbnail ? $primaryFile->thumbnail->url : $primaryFile->url }}" 
                                     alt="Facebook Post"
                                     class="h-full w-full object-cover">
                            </div>
                        @elseif($post->media_url)
                            <div class="aspect-square">
                                <img src="{{ $post->media_url }}" 
                                     alt="Facebook Post"
                                     class="h-full w-full object-cover">
                            </div>
                        @endif

                        {{-- Post Stats --}}
                        <div class="p-2 space-y-2 border-t border-[var(--ui-border)]/60">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-heart', 'w-3 h-3 text-pink-500')
                                        <span>{{ number_format($post->like_count) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-chat-bubble-left', 'w-3 h-3 text-[var(--ui-muted)]')
                                        <span>{{ number_format($post->comment_count) }}</span>
                                    </div>
                                </div>
                                @if($post->published_at)
                                    <span class="text-[var(--ui-muted)]">{{ $post->published_at->format('d.m.y') }}</span>
                                @endif
                            </div>
                            @if($post->message)
                                <p class="text-xs text-[var(--ui-secondary)] line-clamp-2">{{ $post->message }}</p>
                            @elseif($post->story)
                                <p class="text-xs text-[var(--ui-secondary)] line-clamp-2">{{ $post->story }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- List View --}}
            <div x-show="viewMode === 'list'" class="space-y-4">
                @foreach($posts as $post)
                    <div class="border border-[var(--ui-border)]/60 rounded-lg overflow-hidden">
                        <div class="flex">
                            {{-- Post Image --}}
                            <div class="w-48 h-48 flex-shrink-0">
                                @if($post->contextFiles->where('meta.role', 'primary')->first())
                                    @php $primaryFile = $post->contextFiles->where('meta.role', 'primary')->first(); @endphp
                                    <img src="{{ $primaryFile->thumbnail ? $primaryFile->thumbnail->url : $primaryFile->url }}" 
                                         alt="Facebook Post"
                                         class="h-full w-full object-cover">
                                @elseif($post->media_url)
                                    <img src="{{ $post->media_url }}" 
                                         alt="Facebook Post"
                                         class="h-full w-full object-cover">
                                @endif
                            </div>

                            {{-- Post Content --}}
                            <div class="flex-1 p-4 flex flex-col">
                                {{-- Post Header --}}
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-5 h-5 rounded-full bg-[var(--ui-primary-5)] flex items-center justify-center">
                                        @svg('heroicon-o-globe-alt', 'w-3 h-3 text-[var(--ui-primary)]')
                                    </div>
                                    <p class="text-sm font-medium text-[var(--ui-secondary)]">{{ $facebookPage->name }}</p>
                                </div>

                                {{-- Caption --}}
                                @if($post->message)
                                    <p class="text-sm text-[var(--ui-secondary)] line-clamp-3 mb-4">{{ $post->message }}</p>
                                @elseif($post->story)
                                    <p class="text-sm text-[var(--ui-secondary)] line-clamp-3 mb-4">{{ $post->story }}</p>
                                @endif

                                {{-- Post Footer --}}
                                <div class="flex items-center justify-between mt-auto">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                            <span class="text-sm">{{ number_format($post->like_count) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-[var(--ui-muted)]')
                                            <span class="text-sm">{{ number_format($post->comment_count) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-share', 'w-4 h-4 text-[var(--ui-muted)]')
                                            <span class="text-sm">{{ number_format($post->share_count) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        @if($post->published_at)
                                            <span class="text-sm text-[var(--ui-muted)]">
                                                {{ $post->published_at->format('d.m.Y H:i') }}
                                            </span>
                                        @endif
                                        @if($post->permalink_url)
                                            <a href="{{ $post->permalink_url }}" 
                                               target="_blank"
                                               class="text-sm text-[var(--ui-primary)] hover:underline">
                                                Auf Facebook öffnen
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Facebook Page Details" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">External ID</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $facebookPage->external_id }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $facebookPage->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($facebookPage->brand)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Marke</span>
                                <a href="{{ route('brands.brands.show', $facebookPage->brand) }}" class="text-sm text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $facebookPage->brand->name }}
                                </a>
                            </div>
                        @endif
                        @if($facebookPage->posts->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Posts</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $facebookPage->posts->count() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Instagram Accounts --}}
                @if($facebookPage->instagramAccounts->count() > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Instagram Accounts</h3>
                        <div class="space-y-2">
                            @foreach($facebookPage->instagramAccounts as $instagramAccount)
                                <a href="{{ route('brands.instagram-accounts.show', $instagramAccount) }}" 
                                   class="block py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg hover:bg-[var(--ui-primary-5)] transition-colors">
                                    <span class="text-sm text-[var(--ui-secondary)] font-medium">@{{ $instagramAccount->username }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
