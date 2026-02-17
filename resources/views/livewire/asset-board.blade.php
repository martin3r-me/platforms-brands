<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$assetBoard->name" icon="heroicon-o-folder-open">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $assetBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zur&uuml;ck zur Marke</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-100 to-sky-50 flex items-center justify-center">
                        @svg('heroicon-o-folder-open', 'w-6 h-6 text-sky-600')
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">{{ $assetBoard->name }}</h1>
                        @if($assetBoard->description)
                            <p class="text-[var(--ui-muted)] mt-1">{{ $assetBoard->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Bar --}}
        @if($allAssets->count() > 0)
            <div class="flex flex-wrap items-center gap-3">
                {{-- Typ-Filter --}}
                <select wire:model.live="filterType" class="px-3 py-2 text-sm border border-[var(--ui-border)]/60 rounded-lg bg-white text-[var(--ui-secondary)] focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    <option value="">Alle Typen</option>
                    <option value="sm_template">Social Media Template</option>
                    <option value="letterhead">Briefkopf</option>
                    <option value="signature">E-Mail-Signatur</option>
                    <option value="banner">Banner</option>
                    <option value="presentation">Pr&auml;sentation</option>
                    <option value="other">Sonstiges</option>
                </select>

                {{-- Tag-Filter --}}
                <select wire:model.live="filterTag" class="px-3 py-2 text-sm border border-[var(--ui-border)]/60 rounded-lg bg-white text-[var(--ui-secondary)] focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    <option value="">Alle Kan&auml;le</option>
                    @foreach($allTags->sortDesc() as $tag => $count)
                        <option value="{{ $tag }}">{{ $tag }} ({{ $count }})</option>
                    @endforeach
                </select>

                @if($filterType || $filterTag)
                    <button wire:click="$set('filterType', '')" x-on:click="$wire.set('filterTag', '')" class="inline-flex items-center gap-1 px-3 py-2 text-sm text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors">
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                        Filter zur&uuml;cksetzen
                    </button>
                @endif
            </div>
        @endif

        {{-- Upload Area --}}
        @can('update', $assetBoard)
            <div
                x-data="{
                    isDragging: false,
                    handleDrop(event) {
                        this.isDragging = false;
                        const files = event.dataTransfer.files;
                        if (files.length > 0) {
                            @this.uploadMultiple('newFiles', files);
                        }
                    }
                }"
                x-on:dragover.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="handleDrop($event)"
                :class="isDragging ? 'border-sky-400 bg-sky-50/50' : 'border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]'"
                class="border-2 border-dashed rounded-xl p-8 text-center transition-colors"
            >
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-sky-50 mb-3">
                    @svg('heroicon-o-cloud-arrow-up', 'w-6 h-6 text-sky-400')
                </div>
                <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Assets hochladen</p>
                <p class="text-xs text-[var(--ui-muted)] mb-4">Drag & Drop oder Klicken zum Ausw&auml;hlen (max. 50MB pro Datei)</p>
                <label class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-sky-500 hover:bg-sky-600 rounded-lg cursor-pointer transition-colors">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Dateien ausw&auml;hlen</span>
                    <input type="file" wire:model="newFiles" multiple class="hidden">
                </label>
                @error('newFiles.*') <p class="text-sm text-red-500 mt-2">{{ $message }}</p> @enderror

                @if(count($newFiles) > 0)
                    <div class="mt-4 flex items-center justify-center gap-3">
                        <span class="text-sm text-[var(--ui-muted)]">{{ count($newFiles) }} Datei(en) ausgew&auml;hlt</span>
                        <x-ui-button variant="primary" size="sm" wire:click="uploadFiles">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-cloud-arrow-up', 'w-4 h-4')
                                <span>Hochladen</span>
                            </span>
                        </x-ui-button>
                    </div>
                @endif
            </div>
        @endcan

        {{-- Asset Gallery Grid --}}
        @if($assets->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($assets as $asset)
                    <div class="group relative bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-lg transition-all duration-200 overflow-hidden">
                        {{-- Thumbnail / Preview --}}
                        <div class="aspect-[4/3] bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center relative overflow-hidden">
                            @if($asset->mime_type && str_starts_with($asset->mime_type, 'image/'))
                                <img src="{{ asset('storage/' . $asset->file_path) }}" alt="{{ $asset->name }}" class="w-full h-full object-cover" loading="lazy">
                            @else
                                <div class="text-center p-4">
                                    @php
                                        $iconMap = [
                                            'application/pdf' => 'heroicon-o-document-text',
                                            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'heroicon-o-presentation-chart-bar',
                                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'heroicon-o-document',
                                        ];
                                        $icon = $iconMap[$asset->mime_type] ?? 'heroicon-o-document';
                                    @endphp
                                    @svg($icon, 'w-12 h-12 text-gray-300')
                                    <p class="text-xs text-[var(--ui-muted)] mt-2 uppercase font-medium">{{ pathinfo($asset->file_name, PATHINFO_EXTENSION) }}</p>
                                </div>
                            @endif

                            {{-- Version Badge --}}
                            @if($asset->current_version > 1)
                                <div class="absolute top-2 left-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-sky-500 text-white rounded-full">
                                        v{{ $asset->current_version }}
                                    </span>
                                </div>
                            @endif

                            {{-- Asset Type Badge --}}
                            <div class="absolute top-2 right-2">
                                @php
                                    $typeColors = [
                                        'sm_template' => 'bg-purple-100 text-purple-700',
                                        'letterhead' => 'bg-blue-100 text-blue-700',
                                        'signature' => 'bg-green-100 text-green-700',
                                        'banner' => 'bg-orange-100 text-orange-700',
                                        'presentation' => 'bg-rose-100 text-rose-700',
                                        'other' => 'bg-gray-100 text-gray-700',
                                    ];
                                    $typeColor = $typeColors[$asset->asset_type] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-2 py-0.5 text-[10px] font-medium {{ $typeColor }} rounded-full backdrop-blur-sm">
                                    {{ $asset->getAssetTypeLabel() }}
                                </span>
                            </div>

                            {{-- Hover Actions --}}
                            @can('update', $assetBoard)
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center gap-2">
                                    <button
                                        x-data
                                        @click="$dispatch('open-modal-asset', { assetBoardId: {{ $assetBoard->id }}, assetId: {{ $asset->id }} })"
                                        class="p-2 bg-white/90 hover:bg-white rounded-lg text-[var(--ui-secondary)] hover:text-sky-600 transition-colors backdrop-blur-sm"
                                        title="Bearbeiten"
                                    >
                                        @svg('heroicon-o-pencil', 'w-4 h-4')
                                    </button>
                                    <a href="{{ asset('storage/' . $asset->file_path) }}" download="{{ $asset->file_name }}" class="p-2 bg-white/90 hover:bg-white rounded-lg text-[var(--ui-secondary)] hover:text-green-600 transition-colors backdrop-blur-sm" title="Download">
                                        @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                                    </a>
                                    <button
                                        wire:click="deleteAsset({{ $asset->id }})"
                                        wire:confirm="Asset wirklich l&ouml;schen? Alle Versionen werden ebenfalls gel&ouml;scht."
                                        class="p-2 bg-white/90 hover:bg-white rounded-lg text-[var(--ui-secondary)] hover:text-red-600 transition-colors backdrop-blur-sm"
                                        title="L&ouml;schen"
                                    >
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </button>
                                </div>
                            @endcan
                        </div>

                        {{-- Asset Info --}}
                        <div class="p-4">
                            <h4 class="text-sm font-semibold text-[var(--ui-secondary)] truncate mb-1">{{ $asset->name }}</h4>
                            @if($asset->description)
                                <p class="text-xs text-[var(--ui-muted)] line-clamp-2 mb-2">{{ $asset->description }}</p>
                            @endif

                            {{-- Tags --}}
                            @if(!empty($asset->tags))
                                <div class="flex flex-wrap gap-1 mb-2">
                                    @foreach($asset->tags as $tag)
                                        <span class="px-1.5 py-0.5 text-[10px] font-medium bg-sky-50 text-sky-700 border border-sky-200 rounded-full">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Meta --}}
                            <div class="flex items-center justify-between text-xs text-[var(--ui-muted)]">
                                <span>{{ $asset->file_name ? strtoupper(pathinfo($asset->file_name, PATHINFO_EXTENSION)) : '' }}</span>
                                @if($asset->file_size)
                                    <span>{{ number_format($asset->file_size / 1024, 0, ',', '.') }} KB</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Empty State --}}
        @if($allAssets->count() === 0)
            <div class="text-center py-16 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-sky-50 mb-4">
                    @svg('heroicon-o-folder-open', 'w-8 h-8 text-sky-400')
                </div>
                <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Assets vorhanden</p>
                <p class="text-xs text-[var(--ui-muted)] mb-4">Lade Templates, Vorlagen und Brand Assets hoch</p>
                @can('update', $assetBoard)
                    <x-ui-button
                        variant="primary"
                        size="sm"
                        x-data
                        @click="$dispatch('open-modal-asset', { assetBoardId: {{ $assetBoard->id }} })"
                    >
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Asset hinzuf&uuml;gen</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-&Uuml;bersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $assetBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zur&uuml;ck zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $assetBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-asset', { assetBoardId: {{ $assetBoard->id }} })"
                                class="w-full"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Asset hinzuf&uuml;gen</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-asset-board-settings', { assetBoardId: {{ $assetBoard->id }} })" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                    <span>Einstellungen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                </div>

                {{-- Board-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Typ</span>
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-sky-50 text-sky-600 border border-sky-200">
                                Asset Board
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $assetBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($allAssets->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Assets</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $allAssets->count() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Typ-Übersicht --}}
                @if($typeCounts->count() > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Asset-Typen</h3>
                        <div class="space-y-1.5">
                            @php
                                $typeLabels = [
                                    'sm_template' => 'Social Media Template',
                                    'letterhead' => 'Briefkopf',
                                    'signature' => 'E-Mail-Signatur',
                                    'banner' => 'Banner',
                                    'presentation' => 'Pr&auml;sentation',
                                    'other' => 'Sonstiges',
                                ];
                            @endphp
                            @foreach($typeCounts as $type => $count)
                                <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                    <span class="text-xs text-[var(--ui-muted)]">{!! $typeLabels[$type] ?? $type !!}</span>
                                    <span class="text-xs font-medium text-[var(--ui-secondary)]">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Tag-Übersicht --}}
                @if($allTags->count() > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Kanal-Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($allTags->sortDesc() as $tag => $count)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium bg-sky-50 text-sky-700 border border-sky-200 rounded-full">
                                    {{ $tag }}
                                    <span class="text-[10px] bg-sky-100 px-1 rounded-full">{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivit&auml;ten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Letzte Aktivit&auml;ten</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                            <div class="text-sm font-medium text-[var(--ui-secondary)]">{{ $activity['title'] ?? 'Aktivit&auml;t' }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $activity['time'] ?? '' }}</div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                                @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivit&auml;ten</p>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">&Auml;nderungen werden hier angezeigt</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <livewire:brands.asset-board-settings-modal />
    <livewire:brands.asset-modal />
</x-ui-page>
