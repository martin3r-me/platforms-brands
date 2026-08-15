<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$moodboardBoard->name" icon="heroicon-o-photo" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $moodboardBoard->brand->name, 'href' => route('brands.brands.show', $moodboardBoard->brand)],
            ['label' => $moodboardBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $moodboardBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-moodboard-board-settings', { moodboardBoardId: {{ $moodboardBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
            </x-slot>

            @can('update', $moodboardBoard)
                <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-moodboard-image', { moodboardBoardId: {{ $moodboardBoard->id }} })">
                    @svg('heroicon-o-plus', 'w-4 h-4') Bild hinzufügen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $moodboardBoard->name }}</h1>
            @if($moodboardBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $moodboardBoard->description }}</p>
            @endif
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
                :class="isDragging ? 'border-[color:var(--nx-accent)] bg-[color:var(--nx-hover)]' : 'border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)]'"
                class="rounded-[8px] border border-dashed p-8 text-center transition-colors"
            >
                <div class="mb-3 inline-flex items-center justify-center">
                    @svg('heroicon-o-cloud-arrow-up', 'w-6 h-6 text-[color:var(--nx-faint)]')
                </div>
                <p class="mb-1 text-sm font-medium text-[color:var(--nx-text)]">Bilder hochladen</p>
                <p class="mb-4 text-xs text-[color:var(--nx-faint)]">Drag &amp; Drop oder Klicken zum Auswählen (max. 10MB pro Bild)</p>
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-[6px] bg-[color:var(--nx-accent)] px-4 py-2 text-sm font-medium text-[color:var(--nx-on-accent)] transition-colors hover:bg-[color:var(--nx-accent-hover)]">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Bilder auswählen</span>
                    <input type="file" wire:model="newImages" multiple accept="image/*" class="hidden">
                </label>
                @error('newImages.*') <p class="mt-2 text-sm text-[color:var(--nx-danger)]">{{ $message }}</p> @enderror

                @if(count($newImages) > 0)
                    <div class="mt-4 flex items-center justify-center gap-3">
                        <span class="text-sm text-[color:var(--nx-muted)]">{{ count($newImages) }} Bild(er) ausgewählt</span>
                        <x-nx-button variant="primary" size="sm" wire:click="uploadImages">
                            @svg('heroicon-o-cloud-arrow-up', 'w-4 h-4') Hochladen
                        </x-nx-button>
                    </div>
                @endif
            </div>
        @endcan

        {{-- Masonry Grid: Do's (passend) --}}
        @if($doImages->count() > 0)
            <x-nx-section icon="heroicon-o-check" title="Passende Bildsprache" :hint="(string) $doImages->count()">
                <div class="columns-2 gap-4 space-y-4 md:columns-3 lg:columns-4">
                    @foreach($doImages as $img)
                        <x-nx-card flush class="group relative break-inside-avoid overflow-hidden">
                            <img src="{{ $img->image_url }}" alt="{{ $img->title ?? 'Moodboard Bild' }}" class="block w-full" loading="lazy">

                            {{-- Overlay --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                <div class="absolute bottom-0 left-0 right-0 p-3">
                                    @if($img->title)
                                        <p class="mb-1 text-sm font-semibold text-white">{{ $img->title }}</p>
                                    @endif
                                    @if($img->annotation)
                                        <p class="line-clamp-2 text-xs text-white/80">{{ $img->annotation }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Tags --}}
                            @if(!empty($img->tags))
                                <div class="absolute top-2 left-2 flex flex-wrap gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    @foreach($img->tags as $tag)
                                        <span class="rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-medium text-[color:var(--nx-text)] backdrop-blur-sm">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Edit/Delete Actions --}}
                            @can('update', $moodboardBoard)
                                <div class="absolute top-2 right-2 flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button
                                        x-data
                                        @click="$dispatch('open-modal-moodboard-image', { moodboardBoardId: {{ $moodboardBoard->id }}, imageId: {{ $img->id }} })"
                                        class="rounded-[6px] bg-white/90 p-1.5 text-[color:var(--nx-muted)] backdrop-blur-sm transition-colors hover:bg-white hover:text-[color:var(--nx-text)]"
                                        title="Bearbeiten"
                                    >
                                        @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                    </button>
                                    <button
                                        wire:click="deleteImage({{ $img->id }})"
                                        wire:confirm="Bild wirklich löschen?"
                                        class="rounded-[6px] bg-white/90 p-1.5 text-[color:var(--nx-muted)] backdrop-blur-sm transition-colors hover:bg-white hover:text-[color:var(--nx-danger)]"
                                        title="Löschen"
                                    >
                                        @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                                    </button>
                                </div>
                            @endcan
                        </x-nx-card>
                    @endforeach
                </div>
            </x-nx-section>
        @endif

        {{-- Do's & Don'ts Comparison --}}
        @if($dontImages->count() > 0)
            <x-nx-section icon="heroicon-o-x-mark" title="Unpassende Bildsprache" :hint="(string) $dontImages->count()">
                <div class="columns-2 gap-4 space-y-4 md:columns-3 lg:columns-4">
                    @foreach($dontImages as $img)
                        <x-nx-card flush class="group relative break-inside-avoid overflow-hidden">
                            <div class="relative">
                                <img src="{{ $img->image_url }}" alt="{{ $img->title ?? 'Don\'t Beispiel' }}" class="block w-full opacity-75" loading="lazy">
                                {{-- Don't Badge --}}
                                <div class="absolute top-2 left-2">
                                    <x-nx-badge variant="danger">
                                        @svg('heroicon-o-x-mark', 'w-3 h-3')
                                        Don't
                                    </x-nx-badge>
                                </div>
                            </div>

                            {{-- Overlay --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                <div class="absolute bottom-0 left-0 right-0 p-3">
                                    @if($img->title)
                                        <p class="mb-1 text-sm font-semibold text-white">{{ $img->title }}</p>
                                    @endif
                                    @if($img->annotation)
                                        <p class="line-clamp-2 text-xs text-white/80">{{ $img->annotation }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Tags --}}
                            @if(!empty($img->tags))
                                <div class="absolute top-2 right-2 flex flex-wrap gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    @foreach($img->tags as $tag)
                                        <span class="rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-medium text-[color:var(--nx-text)] backdrop-blur-sm">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Edit/Delete Actions --}}
                            @can('update', $moodboardBoard)
                                <div class="absolute top-10 right-2 flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button
                                        x-data
                                        @click="$dispatch('open-modal-moodboard-image', { moodboardBoardId: {{ $moodboardBoard->id }}, imageId: {{ $img->id }} })"
                                        class="rounded-[6px] bg-white/90 p-1.5 text-[color:var(--nx-muted)] backdrop-blur-sm transition-colors hover:bg-white hover:text-[color:var(--nx-text)]"
                                        title="Bearbeiten"
                                    >
                                        @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                    </button>
                                    <button
                                        wire:click="deleteImage({{ $img->id }})"
                                        wire:confirm="Bild wirklich löschen?"
                                        class="rounded-[6px] bg-white/90 p-1.5 text-[color:var(--nx-muted)] backdrop-blur-sm transition-colors hover:bg-white hover:text-[color:var(--nx-danger)]"
                                        title="Löschen"
                                    >
                                        @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                                    </button>
                                </div>
                            @endcan
                        </x-nx-card>
                    @endforeach
                </div>
            </x-nx-section>
        @endif

        {{-- Empty State --}}
        @if($allImages->count() === 0)
            <x-nx-empty icon="heroicon-o-photo">
                Noch keine Bilder vorhanden – lade Referenzbilder hoch, um die Bildsprache deiner Marke zu definieren.
                @can('update', $moodboardBoard)
                    <x-slot name="action">
                        <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-moodboard-image', { moodboardBoardId: {{ $moodboardBoard->id }} })">
                            @svg('heroicon-o-plus', 'w-3.5 h-3.5') Bild hinzufügen
                        </x-nx-button>
                    </x-slot>
                @endcan
            </x-nx-empty>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        @php
            $moodDetailRows = [
                ['label' => 'Typ', 'value' => 'Moodboard'],
                ['label' => 'Erstellt', 'value' => $moodboardBoard->created_at->format('d.m.Y')],
            ];
            if ($allImages->count() > 0) {
                $moodDetailRows[] = ['label' => 'Bilder', 'value' => (string) $allImages->count()];
            }
            if ($doImages->count() > 0) {
                $moodDetailRows[] = ['label' => 'Passend', 'value' => (string) $doImages->count()];
            }
            if ($dontImages->count() > 0) {
                $moodDetailRows[] = ['label' => 'Unpassend', 'value' => (string) $dontImages->count()];
            }
        @endphp
        @include('brands::partials.board-sidebar', ['detailRows' => $moodDetailRows])
    </x-slot>

    <x-slot name="activity">
        @include('brands::partials.board-activity')
    </x-slot>

    <livewire:brands.moodboard-board-settings-modal />
    <livewire:brands.moodboard-image-modal />
</x-ui-page>
