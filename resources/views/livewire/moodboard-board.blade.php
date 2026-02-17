<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$moodboardBoard->name" icon="heroicon-o-photo">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $moodboardBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zurück zur Marke</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-100 to-rose-50 flex items-center justify-center">
                        @svg('heroicon-o-photo', 'w-6 h-6 text-rose-600')
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">{{ $moodboardBoard->name }}</h1>
                        @if($moodboardBoard->description)
                            <p class="text-[var(--ui-muted)] mt-1">{{ $moodboardBoard->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Upload Area --}}
        @can('update', $moodboardBoard)
            <div
                x-data="{
                    isDragging: false,
                    handleDrop(event) {
                        this.isDragging = false;
                        const files = event.dataTransfer.files;
                        if (files.length > 0) {
                            @this.uploadMultiple('newImages', files);
                        }
                    }
                }"
                x-on:dragover.prevent="isDragging = true"
                x-on:dragleave.prevent="isDragging = false"
                x-on:drop.prevent="handleDrop($event)"
                :class="isDragging ? 'border-rose-400 bg-rose-50/50' : 'border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]'"
                class="border-2 border-dashed rounded-xl p-8 text-center transition-colors"
            >
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-rose-50 mb-3">
                    @svg('heroicon-o-cloud-arrow-up', 'w-6 h-6 text-rose-400')
                </div>
                <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Bilder hochladen</p>
                <p class="text-xs text-[var(--ui-muted)] mb-4">Drag & Drop oder Klicken zum Auswählen (max. 10MB pro Bild)</p>
                <label class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-rose-500 hover:bg-rose-600 rounded-lg cursor-pointer transition-colors">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Bilder auswählen</span>
                    <input type="file" wire:model="newImages" multiple accept="image/*" class="hidden">
                </label>
                @error('newImages.*') <p class="text-sm text-red-500 mt-2">{{ $message }}</p> @enderror

                @if(count($newImages) > 0)
                    <div class="mt-4 flex items-center justify-center gap-3">
                        <span class="text-sm text-[var(--ui-muted)]">{{ count($newImages) }} Bild(er) ausgewählt</span>
                        <x-ui-button variant="primary" size="sm" wire:click="uploadImages">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-cloud-arrow-up', 'w-4 h-4')
                                <span>Hochladen</span>
                            </span>
                        </x-ui-button>
                    </div>
                @endif
            </div>
        @endcan

        {{-- Masonry Grid: Do's (passend) --}}
        @if($doImages->count() > 0)
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100">
                        @svg('heroicon-o-check', 'w-5 h-5 text-green-600')
                    </span>
                    <h2 class="text-xl font-bold text-[var(--ui-secondary)]">Passende Bildsprache</h2>
                    <span class="text-sm text-[var(--ui-muted)]">({{ $doImages->count() }})</span>
                </div>

                <div class="columns-2 md:columns-3 lg:columns-4 gap-4 space-y-4">
                    @foreach($doImages as $img)
                        <div class="group relative break-inside-avoid rounded-xl overflow-hidden border border-[var(--ui-border)]/60 bg-white shadow-sm hover:shadow-lg transition-all duration-200">
                            <img src="{{ asset('storage/' . $img->image_path) }}" alt="{{ $img->title ?? 'Moodboard Bild' }}" class="w-full block" loading="lazy">

                            {{-- Overlay --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <div class="absolute bottom-0 left-0 right-0 p-3">
                                    @if($img->title)
                                        <p class="text-sm font-semibold text-white mb-1">{{ $img->title }}</p>
                                    @endif
                                    @if($img->annotation)
                                        <p class="text-xs text-white/80 line-clamp-2">{{ $img->annotation }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Tags --}}
                            @if(!empty($img->tags))
                                <div class="absolute top-2 left-2 flex flex-wrap gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @foreach($img->tags as $tag)
                                        <span class="px-2 py-0.5 text-[10px] font-medium bg-white/90 text-[var(--ui-secondary)] rounded-full backdrop-blur-sm">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Edit/Delete Actions --}}
                            @can('update', $moodboardBoard)
                                <div class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        x-data
                                        @click="$dispatch('open-modal-moodboard-image', { moodboardBoardId: {{ $moodboardBoard->id }}, imageId: {{ $img->id }} })"
                                        class="p-1.5 bg-white/90 hover:bg-white rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-primary)] transition-colors backdrop-blur-sm"
                                        title="Bearbeiten"
                                    >
                                        @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                    </button>
                                    <button
                                        wire:click="deleteImage({{ $img->id }})"
                                        wire:confirm="Bild wirklich löschen?"
                                        class="p-1.5 bg-white/90 hover:bg-white rounded-lg text-[var(--ui-muted)] hover:text-red-600 transition-colors backdrop-blur-sm"
                                        title="Löschen"
                                    >
                                        @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                                    </button>
                                </div>
                            @endcan
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Do's & Don'ts Comparison --}}
        @if($dontImages->count() > 0)
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100">
                        @svg('heroicon-o-x-mark', 'w-5 h-5 text-red-600')
                    </span>
                    <h2 class="text-xl font-bold text-[var(--ui-secondary)]">Unpassende Bildsprache</h2>
                    <span class="text-sm text-[var(--ui-muted)]">({{ $dontImages->count() }})</span>
                </div>

                <div class="columns-2 md:columns-3 lg:columns-4 gap-4 space-y-4">
                    @foreach($dontImages as $img)
                        <div class="group relative break-inside-avoid rounded-xl overflow-hidden border-2 border-red-200 bg-white shadow-sm hover:shadow-lg transition-all duration-200">
                            <div class="relative">
                                <img src="{{ asset('storage/' . $img->image_path) }}" alt="{{ $img->title ?? 'Don\'t Beispiel' }}" class="w-full block opacity-75" loading="lazy">
                                {{-- Don't Badge --}}
                                <div class="absolute top-2 left-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-bold uppercase tracking-wider bg-red-500 text-white rounded-full">
                                        @svg('heroicon-o-x-mark', 'w-3 h-3')
                                        Don't
                                    </span>
                                </div>
                            </div>

                            {{-- Overlay --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <div class="absolute bottom-0 left-0 right-0 p-3">
                                    @if($img->title)
                                        <p class="text-sm font-semibold text-white mb-1">{{ $img->title }}</p>
                                    @endif
                                    @if($img->annotation)
                                        <p class="text-xs text-white/80 line-clamp-2">{{ $img->annotation }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Tags --}}
                            @if(!empty($img->tags))
                                <div class="absolute top-2 right-2 flex flex-wrap gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @foreach($img->tags as $tag)
                                        <span class="px-2 py-0.5 text-[10px] font-medium bg-white/90 text-[var(--ui-secondary)] rounded-full backdrop-blur-sm">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Edit/Delete Actions --}}
                            @can('update', $moodboardBoard)
                                <div class="absolute top-10 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        x-data
                                        @click="$dispatch('open-modal-moodboard-image', { moodboardBoardId: {{ $moodboardBoard->id }}, imageId: {{ $img->id }} })"
                                        class="p-1.5 bg-white/90 hover:bg-white rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-primary)] transition-colors backdrop-blur-sm"
                                        title="Bearbeiten"
                                    >
                                        @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                    </button>
                                    <button
                                        wire:click="deleteImage({{ $img->id }})"
                                        wire:confirm="Bild wirklich löschen?"
                                        class="p-1.5 bg-white/90 hover:bg-white rounded-lg text-[var(--ui-muted)] hover:text-red-600 transition-colors backdrop-blur-sm"
                                        title="Löschen"
                                    >
                                        @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                                    </button>
                                </div>
                            @endcan
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Empty State --}}
        @if($allImages->count() === 0)
            <div class="text-center py-16 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-rose-50 mb-4">
                    @svg('heroicon-o-photo', 'w-8 h-8 text-rose-400')
                </div>
                <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Bilder vorhanden</p>
                <p class="text-xs text-[var(--ui-muted)] mb-4">Lade Referenzbilder hoch, um die Bildsprache deiner Marke zu definieren</p>
                @can('update', $moodboardBoard)
                    <x-ui-button
                        variant="primary"
                        size="sm"
                        x-data
                        @click="$dispatch('open-modal-moodboard-image', { moodboardBoardId: {{ $moodboardBoard->id }} })"
                    >
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Bild hinzufügen</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $moodboardBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurück zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $moodboardBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-moodboard-image', { moodboardBoardId: {{ $moodboardBoard->id }} })"
                                class="w-full"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Bild hinzufügen</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-moodboard-board-settings', { moodboardBoardId: {{ $moodboardBoard->id }} })" class="w-full">
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
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-rose-50 text-rose-600 border border-rose-200">
                                Moodboard
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $moodboardBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($allImages->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Bilder</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $allImages->count() }}
                                </span>
                            </div>
                        @endif
                        @if($doImages->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Passend</span>
                                <span class="text-sm text-green-600 font-medium">{{ $doImages->count() }}</span>
                            </div>
                        @endif
                        @if($dontImages->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Unpassend</span>
                                <span class="text-sm text-red-600 font-medium">{{ $dontImages->count() }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tag-Übersicht --}}
                @php
                    $allTags = $allImages->pluck('tags')->filter()->flatten()->countBy();
                @endphp
                @if($allTags->count() > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($allTags->sortDesc() as $tag => $count)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200 rounded-full">
                                    {{ $tag }}
                                    <span class="text-[10px] bg-rose-100 px-1 rounded-full">{{ $count }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Letzte Aktivitäten</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                            <div class="text-sm font-medium text-[var(--ui-secondary)]">{{ $activity['title'] ?? 'Aktivität' }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $activity['time'] ?? '' }}</div>
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

    <livewire:brands.moodboard-board-settings-modal />
    <livewire:brands.moodboard-image-modal />
</x-ui-page>
