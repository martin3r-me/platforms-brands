<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$assetBoard->name" icon="heroicon-o-folder-open" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $assetBoard->brand->name, 'href' => route('brands.brands.show', $assetBoard->brand)],
            ['label' => $assetBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $assetBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-asset-board-settings', { assetBoardId: {{ $assetBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
            </x-slot>

            @can('update', $assetBoard)
                <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-asset', { assetBoardId: {{ $assetBoard->id }} })">
                    @svg('heroicon-o-plus', 'w-4 h-4') Asset hinzufügen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $assetBoard->name }}</h1>
            @if($assetBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $assetBoard->description }}</p>
            @endif
        </div>

        {{-- Filter Bar --}}
        @if($allAssets->count() > 0)
            <div class="flex flex-wrap items-center gap-3">
                {{-- Typ-Filter --}}
                <select wire:model.live="filterType" class="rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                    <option value="">Alle Typen</option>
                    <option value="sm_template">Social Media Template</option>
                    <option value="letterhead">Briefkopf</option>
                    <option value="signature">E-Mail-Signatur</option>
                    <option value="banner">Banner</option>
                    <option value="presentation">Präsentation</option>
                    <option value="other">Sonstiges</option>
                </select>

                {{-- Tag-Filter --}}
                <select wire:model.live="filterTag" class="rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                    <option value="">Alle Kanäle</option>
                    @foreach($allTags->sortDesc() as $tag => $count)
                        <option value="{{ $tag }}">{{ $tag }} ({{ $count }})</option>
                    @endforeach
                </select>

                @if($filterType || $filterTag)
                    <button wire:click="$set('filterType', '')" x-on:click="$wire.set('filterTag', '')" class="inline-flex items-center gap-1 px-3 py-2 text-sm text-[color:var(--nx-faint)] transition-colors hover:text-[color:var(--nx-text)]">
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                        Filter zurücksetzen
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
                :class="isDragging ? 'border-[color:var(--nx-accent)] bg-[color:var(--nx-hover)]' : 'border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)]'"
                class="rounded-[8px] border border-dashed p-8 text-center transition-colors"
            >
                <div class="mb-3 inline-flex items-center justify-center">
                    @svg('heroicon-o-cloud-arrow-up', 'w-6 h-6 text-[color:var(--nx-faint)]')
                </div>
                <p class="mb-1 text-sm font-medium text-[color:var(--nx-text)]">Assets hochladen</p>
                <p class="mb-4 text-xs text-[color:var(--nx-faint)]">Drag &amp; Drop oder Klicken zum Auswählen (max. 50MB pro Datei)</p>
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-[6px] bg-[color:var(--nx-accent)] px-4 py-2 text-sm font-medium text-[color:var(--nx-on-accent)] transition-colors hover:bg-[color:var(--nx-accent-hover)]">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Dateien auswählen</span>
                    <input type="file" wire:model="newFiles" multiple class="hidden">
                </label>
                @error('newFiles.*') <p class="mt-2 text-sm text-[color:var(--nx-danger)]">{{ $message }}</p> @enderror

                @if(count($newFiles) > 0)
                    <div class="mt-4 flex items-center justify-center gap-3">
                        <span class="text-sm text-[color:var(--nx-muted)]">{{ count($newFiles) }} Datei(en) ausgewählt</span>
                        <x-nx-button variant="primary" size="sm" wire:click="uploadFiles">
                            @svg('heroicon-o-cloud-arrow-up', 'w-4 h-4') Hochladen
                        </x-nx-button>
                    </div>
                @endif
            </div>
        @endcan

        {{-- Asset Gallery Grid --}}
        @if($assets->count() > 0)
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($assets as $asset)
                    <x-nx-card flush class="group relative overflow-hidden">
                        {{-- Thumbnail / Preview --}}
                        <div class="relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-[color:var(--nx-hover)]">
                            @if($asset->mime_type && str_starts_with($asset->mime_type, 'image/'))
                                <img src="{{ asset('storage/' . $asset->file_path) }}" alt="{{ $asset->name }}" class="h-full w-full object-cover" loading="lazy">
                            @else
                                <div class="p-4 text-center">
                                    @php
                                        $iconMap = [
                                            'application/pdf' => 'heroicon-o-document-text',
                                            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'heroicon-o-presentation-chart-bar',
                                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'heroicon-o-document',
                                        ];
                                        $icon = $iconMap[$asset->mime_type] ?? 'heroicon-o-document';
                                    @endphp
                                    @svg($icon, 'w-12 h-12 text-[color:var(--nx-faint)]')
                                    <p class="mt-2 text-xs font-medium uppercase text-[color:var(--nx-faint)]">{{ pathinfo($asset->file_name, PATHINFO_EXTENSION) }}</p>
                                </div>
                            @endif

                            {{-- Version Badge --}}
                            @if($asset->current_version > 1)
                                <div class="absolute top-2 left-2">
                                    <x-nx-badge variant="accent">v{{ $asset->current_version }}</x-nx-badge>
                                </div>
                            @endif

                            {{-- Asset Type Badge --}}
                            <div class="absolute top-2 right-2">
                                <x-nx-badge variant="neutral">{{ $asset->getAssetTypeLabel() }}</x-nx-badge>
                            </div>

                            {{-- Hover Actions --}}
                            @can('update', $assetBoard)
                                <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/40 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                    <button
                                        x-data
                                        @click="$dispatch('open-modal-asset', { assetBoardId: {{ $assetBoard->id }}, assetId: {{ $asset->id }} })"
                                        class="rounded-[6px] bg-white/90 p-2 text-[color:var(--nx-text)] backdrop-blur-sm transition-colors hover:bg-white"
                                        title="Bearbeiten"
                                    >
                                        @svg('heroicon-o-pencil', 'w-4 h-4')
                                    </button>
                                    <a href="{{ asset('storage/' . $asset->file_path) }}" download="{{ $asset->file_name }}" class="rounded-[6px] bg-white/90 p-2 text-[color:var(--nx-text)] backdrop-blur-sm transition-colors hover:bg-white" title="Download">
                                        @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                                    </a>
                                    <button
                                        wire:click="deleteAsset({{ $asset->id }})"
                                        wire:confirm="Asset wirklich löschen? Alle Versionen werden ebenfalls gelöscht."
                                        class="rounded-[6px] bg-white/90 p-2 text-[color:var(--nx-text)] backdrop-blur-sm transition-colors hover:bg-white hover:text-[color:var(--nx-danger)]"
                                        title="Löschen"
                                    >
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </button>
                                </div>
                            @endcan
                        </div>

                        {{-- Asset Info --}}
                        <div class="p-4">
                            <h4 class="mb-1 truncate text-sm font-semibold text-[color:var(--nx-text)]">{{ $asset->name }}</h4>
                            @if($asset->description)
                                <p class="mb-2 line-clamp-2 text-xs text-[color:var(--nx-muted)]">{{ $asset->description }}</p>
                            @endif

                            {{-- Tags --}}
                            @if(!empty($asset->tags))
                                <div class="mb-2 flex flex-wrap gap-1">
                                    @foreach($asset->tags as $tag)
                                        <x-nx-badge variant="info">{{ $tag }}</x-nx-badge>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Meta --}}
                            <div class="flex items-center justify-between text-xs text-[color:var(--nx-faint)]">
                                <span>{{ $asset->file_name ? strtoupper(pathinfo($asset->file_name, PATHINFO_EXTENSION)) : '' }}</span>
                                @if($asset->file_size)
                                    <span>{{ number_format($asset->file_size / 1024, 0, ',', '.') }} KB</span>
                                @endif
                            </div>
                        </div>
                    </x-nx-card>
                @endforeach
            </div>
        @endif

        {{-- Empty State --}}
        @if($allAssets->count() === 0)
            <x-nx-empty icon="heroicon-o-folder-open">
                Noch keine Assets vorhanden – lade Templates, Vorlagen und Brand Assets hoch.
                @can('update', $assetBoard)
                    <x-slot name="action">
                        <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-asset', { assetBoardId: {{ $assetBoard->id }} })">
                            @svg('heroicon-o-plus', 'w-3.5 h-3.5') Asset hinzufügen
                        </x-nx-button>
                    </x-slot>
                @endcan
            </x-nx-empty>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        @php
            $assetDetailRows = [
                ['label' => 'Typ', 'value' => 'Asset Board'],
                ['label' => 'Erstellt', 'value' => $assetBoard->created_at->format('d.m.Y')],
            ];
            if ($allAssets->count() > 0) {
                $assetDetailRows[] = ['label' => 'Assets', 'value' => (string) $allAssets->count()];
            }
            if ($typeCounts->count() > 0) {
                $typeLabels = [
                    'sm_template' => 'Social Media Template',
                    'letterhead' => 'Briefkopf',
                    'signature' => 'E-Mail-Signatur',
                    'banner' => 'Banner',
                    'presentation' => 'Präsentation',
                    'other' => 'Sonstiges',
                ];
                foreach ($typeCounts as $type => $count) {
                    $assetDetailRows[] = ['label' => $typeLabels[$type] ?? $type, 'value' => (string) $count];
                }
            }
        @endphp
        @include('brands::partials.board-sidebar', ['detailRows' => $assetDetailRows])
    </x-slot>


    <livewire:brands.asset-board-settings-modal />
    <livewire:brands.asset-modal />
</x-ui-page>
