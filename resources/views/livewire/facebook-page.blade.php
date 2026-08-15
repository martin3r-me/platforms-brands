<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$facebookPage->name" icon="heroicon-o-globe-alt" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $facebookPage->brand->name ?? 'Marke', 'href' => $facebookPage->brand ? route('brands.brands.show', $facebookPage->brand) : '#'],
            ['label' => $facebookPage->name],
        ]">
            @can('update', $facebookPage)
                <x-nx-button variant="primary" size="sm" wire:click="syncPosts">
                    @svg('heroicon-o-arrow-path', 'w-4 h-4')
                    <span>Posts synchronisieren</span>
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold text-[color:var(--nx-text)]">{{ $facebookPage->name }}</h1>
            <p class="mt-1.5 text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">Facebook Page · {{ $posts->count() }} {{ $posts->count() === 1 ? 'Post' : 'Posts' }}</p>
        </div>

        {{-- Aktueller Content --}}
        <x-nx-section icon="heroicon-o-information-circle" title="Aktueller Content" description="Übersicht der letzten Aktivitäten">
            @if($lastPost = $posts->first())
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {{-- Post Preview --}}
                    <x-nx-card flush class="w-full max-w-sm overflow-hidden">
                        {{-- Post Header --}}
                        <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] p-3">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)]">
                                @svg('heroicon-o-globe-alt', 'w-4 h-4 text-[color:var(--nx-muted)]')
                            </div>
                            <p class="text-sm font-medium text-[color:var(--nx-text)]">{{ $facebookPage->name }}</p>
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
                        <div class="space-y-2 p-3 text-xs">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1">
                                    @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                    <span>{{ number_format($lastPost->like_count) }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                    <span>{{ number_format($lastPost->comment_count) }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    @svg('heroicon-o-share', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                    <span>{{ number_format($lastPost->share_count) }}</span>
                                </div>
                            </div>
                            @if($lastPost->message)
                                <p class="line-clamp-3 text-[color:var(--nx-text)]">{{ $lastPost->message }}</p>
                            @endif
                        </div>
                    </x-nx-card>

                    {{-- Recent Performance --}}
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-[color:var(--nx-text)]">Performance der letzten 7 Tage</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <x-nx-stat label="Likes gesamt" :value="number_format($posts->sum('like_count'))" icon="heroicon-o-heart" />
                            <x-nx-stat label="Kommentare gesamt" :value="number_format($posts->sum('comment_count'))" icon="heroicon-o-chat-bubble-left" />
                        </div>
                    </div>
                </div>
            @else
                <x-nx-empty icon="heroicon-o-information-circle">Noch keine Posts – es wurden noch keine Facebook Posts synchronisiert.</x-nx-empty>
            @endif
        </x-nx-section>

        {{-- Letzte Posts --}}
        <x-nx-section x-data="{ viewMode: 'grid' }" icon="heroicon-o-photo" title="Letzte Posts" :hint="$posts->count() . ' Posts'" description="Die neuesten Facebook Page Posts">
            <x-slot name="action">
                <x-nx-button variant="ghost" size="sm" icon @click="viewMode = viewMode === 'grid' ? 'list' : 'grid'">
                    @svg('heroicon-o-squares-2x2', 'w-5 h-5', ['x-show' => 'viewMode === "list"'])
                    @svg('heroicon-o-bars-4', 'w-5 h-5', ['x-show' => 'viewMode === "grid"'])
                </x-nx-button>
            </x-slot>

            {{-- Grid View --}}
            <div x-show="viewMode === 'grid'"
                 class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($posts as $post)
                    <x-nx-card flush class="overflow-hidden transition-colors hover:bg-[color:var(--nx-hover)]">
                        {{-- Post Header --}}
                        <div class="flex items-center gap-2 border-b border-[color:var(--nx-line)] p-2">
                            <div class="flex h-4 w-4 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)]">
                                @svg('heroicon-o-globe-alt', 'w-3 h-3 text-[color:var(--nx-muted)]')
                            </div>
                            <p class="truncate text-xs font-medium text-[color:var(--nx-text)]">{{ $facebookPage->name }}</p>
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
                        <div class="space-y-2 border-t border-[color:var(--nx-line)] p-2">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-heart', 'w-3 h-3 text-pink-500')
                                        <span>{{ number_format($post->like_count) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @svg('heroicon-o-chat-bubble-left', 'w-3 h-3 text-[color:var(--nx-faint)]')
                                        <span>{{ number_format($post->comment_count) }}</span>
                                    </div>
                                </div>
                                @if($post->published_at)
                                    <span class="text-[color:var(--nx-faint)]">{{ $post->published_at->format('d.m.y') }}</span>
                                @endif
                            </div>
                            @if($post->message)
                                <p class="line-clamp-2 text-xs text-[color:var(--nx-text)]">{{ $post->message }}</p>
                            @elseif($post->story)
                                <p class="line-clamp-2 text-xs text-[color:var(--nx-text)]">{{ $post->story }}</p>
                            @endif
                        </div>
                    </x-nx-card>
                @endforeach
            </div>

            {{-- List View --}}
            <div x-show="viewMode === 'list'" class="space-y-4">
                @foreach($posts as $post)
                    <x-nx-card flush class="overflow-hidden">
                        <div class="flex">
                            {{-- Post Image --}}
                            <div class="h-48 w-48 flex-shrink-0">
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
                            <div class="flex flex-1 flex-col p-4">
                                {{-- Post Header --}}
                                <div class="mb-3 flex items-center gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)]">
                                        @svg('heroicon-o-globe-alt', 'w-3 h-3 text-[color:var(--nx-muted)]')
                                    </div>
                                    <p class="text-sm font-medium text-[color:var(--nx-text)]">{{ $facebookPage->name }}</p>
                                </div>

                                {{-- Caption --}}
                                @if($post->message)
                                    <p class="mb-4 line-clamp-3 text-sm text-[color:var(--nx-text)]">{{ $post->message }}</p>
                                @elseif($post->story)
                                    <p class="mb-4 line-clamp-3 text-sm text-[color:var(--nx-text)]">{{ $post->story }}</p>
                                @endif

                                {{-- Post Footer --}}
                                <div class="mt-auto flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-heart', 'w-4 h-4 text-pink-500')
                                            <span class="text-sm">{{ number_format($post->like_count) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-chat-bubble-left', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                            <span class="text-sm">{{ number_format($post->comment_count) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @svg('heroicon-o-share', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                            <span class="text-sm">{{ number_format($post->share_count) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        @if($post->published_at)
                                            <span class="text-sm text-[color:var(--nx-faint)]">
                                                {{ $post->published_at->format('d.m.Y H:i') }}
                                            </span>
                                        @endif
                                        @if($post->permalink_url)
                                            <a href="{{ $post->permalink_url }}"
                                               target="_blank"
                                               class="text-sm text-[color:var(--nx-accent)] hover:underline">
                                                Auf Facebook öffnen
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-nx-card>
                @endforeach
            </div>
        </x-nx-section>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Facebook Page Details" width="w-80" :defaultOpen="true">
            <div class="p-5 space-y-5">
                {{-- Details --}}
                <div>
                    <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Details</h3>
                    <div class="overflow-hidden rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] divide-y divide-[color:var(--nx-line)]">
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">External ID</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $facebookPage->external_id }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Erstellt</span>
                            <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $facebookPage->created_at->format('d.m.Y') }}</span>
                        </div>
                        @if($facebookPage->brand)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Marke</span>
                                <a href="{{ route('brands.brands.show', $facebookPage->brand) }}" class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-info)] hover:underline">{{ $facebookPage->brand->name }}</a>
                            </div>
                        @endif
                        @if($facebookPage->posts->count() > 0)
                            <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Posts</span>
                                <span class="min-w-0 truncate text-right text-[13px] text-[color:var(--nx-text)]">{{ $facebookPage->posts->count() }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Instagram Accounts --}}
                @if($facebookPage->instagramAccounts->count() > 0)
                    <div>
                        <h3 class="mb-2.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Instagram Accounts</h3>
                        <div class="space-y-2">
                            @foreach($facebookPage->instagramAccounts as $instagramAccount)
                                <a href="{{ route('brands.instagram-accounts.show', $instagramAccount) }}"
                                   class="block rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 transition-colors hover:bg-[color:var(--nx-hover)]">
                                    <span class="text-[13px] font-medium text-[color:var(--nx-text)]">{{ '@' . $instagramAccount->username }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">@include('brands::partials.board-activity')</x-slot>
</x-ui-page>
