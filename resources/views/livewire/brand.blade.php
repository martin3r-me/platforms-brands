<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$brand->name" icon="heroicon-o-tag" />
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $brand->name }}</h1>
                
                @if($brand->description)
                    <div class="mt-4">
                        <p class="text-[var(--ui-secondary)]">{{ $brand->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Boards Section --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Boards</h2>
                @can('update', $brand)
                    <div class="flex items-center gap-2">
                        <x-ui-button variant="primary" size="sm" wire:click="createContentBoard">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Content Board erstellen</span>
                            </span>
                        </x-ui-button>
                        <x-ui-button variant="primary" size="sm" wire:click="createCiBoard">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>CI Board erstellen</span>
                            </span>
                        </x-ui-button>
                    </div>
                @endcan
            </div>

            @php
                $allBoards = $ciBoards->concat($contentBoards)->sortBy('order');
            @endphp

            @if($allBoards->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($allBoards as $board)
                        @if($board instanceof \Platform\Brands\Models\BrandsCiBoard)
                            <a href="{{ route('brands.ci-boards.show', $board) }}" class="block">
                                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md transition-shadow p-6 h-full">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1">{{ $board->name }}</h3>
                                            @if($board->description)
                                                <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ $board->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 mt-4">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-[var(--ui-primary-5)] text-[var(--ui-primary)] text-xs font-medium">
                                            @svg('heroicon-o-paint-brush', 'w-3 h-3')
                                            CI Board
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @else
                            <a href="{{ route('brands.content-boards.show', $board) }}" class="block">
                                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md transition-shadow p-6 h-full">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1">{{ $board->name }}</h3>
                                            @if($board->description)
                                                <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ $board->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 mt-4">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-[var(--ui-primary-5)] text-[var(--ui-primary)] text-xs font-medium">
                                            @svg('heroicon-o-document-text', 'w-3 h-3')
                                            Content Board
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                        @svg('heroicon-o-squares-2x2', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Boards</h3>
                    <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle dein erstes Board für diese Marke.</p>
                    @can('update', $brand)
                        <div class="flex items-center justify-center gap-2">
                            <x-ui-button variant="primary" size="sm" wire:click="createContentBoard">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Content Board erstellen</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="primary" size="sm" wire:click="createCiBoard">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>CI Board erstellen</span>
                                </span>
                            </x-ui-button>
                        </div>
                    @endcan
                </div>
            @endif
        </div>

        {{-- Social Media Section --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Social Media</h2>
                @can('update', $brand)
                    @if($facebookPages->isEmpty())
                        <x-ui-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-facebook-page', { brandId: {{ $brand->id }} })">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Facebook Page anfügen</span>
                            </span>
                        </x-ui-button>
                    @endif
                @endcan
            </div>

            @if($facebookPages->count() > 0 || $instagramAccounts->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($facebookPages as $facebookPage)
                        <a href="{{ route('brands.facebook-pages.show', $facebookPage) }}" class="block">
                            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md transition-shadow p-6 h-full">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1">{{ $facebookPage->name }}</h3>
                                        @if($facebookPage->description)
                                            <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ $facebookPage->description }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2 mt-4">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-[var(--ui-primary-5)] text-[var(--ui-primary)] text-xs font-medium">
                                        @svg('heroicon-o-globe-alt', 'w-3 h-3')
                                        Facebook Page
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach

                    @foreach($instagramAccounts as $instagramAccount)
                        <a href="{{ route('brands.instagram-accounts.show', $instagramAccount) }}" class="block">
                            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md transition-shadow p-6 h-full">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-1">{{ $instagramAccount->username }}</h3>
                                        @if($instagramAccount->description)
                                            <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ $instagramAccount->description }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2 mt-4">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-[var(--ui-primary-5)] text-[var(--ui-primary)] text-xs font-medium">
                                        @svg('heroicon-o-camera', 'w-3 h-3')
                                        Instagram
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                        @svg('heroicon-o-share', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Social Media Accounts</h3>
                    <p class="text-sm text-[var(--ui-muted)] mb-4">Füge eine Facebook Page hinzu, um zu beginnen.</p>
                    @can('update', $brand)
                        <x-ui-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-facebook-page', { brandId: {{ $brand->id }} })">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Facebook Page anfügen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Marken-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $brand)
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-brand-settings', { brandId: {{ $brand->id }} })" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                                    <span>Einstellungen</span>
                                </span>
                            </x-ui-button>
                        @endcan
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
