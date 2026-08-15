<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$ciBoard->name" icon="heroicon-o-paint-brush" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $ciBoard->brand->name, 'href' => route('brands.brands.show', $ciBoard->brand)],
            ['label' => $ciBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $ciBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-ci-board-settings', { ciBoardId: {{ $ciBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
            </x-slot>

            @can('update', $ciBoard)
                @if($this->isDirty())
                    <x-nx-button variant="primary" size="sm" wire:click="save">
                        @svg('heroicon-o-check', 'w-4 h-4') Speichern
                    </x-nx-button>
                @endif
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $ciBoard->name }}</h1>
            @if($ciBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $ciBoard->description }}</p>
            @endif
        </div>

        {{-- Farben --}}
        <x-nx-section icon="heroicon-o-paint-brush" title="Farben" :hint="$ciBoard->colors->count() . ' Palette'">
            @can('update', $ciBoard)
                <x-slot name="action">
                    <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-ci-board-color', { ciBoardId: {{ $ciBoard->id }} })">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5') Farbe
                    </x-nx-button>
                </x-slot>
            @endcan

            {{-- Basis-Farben (editierbar) --}}
            <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Basis</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach([['primary_color','Primär'],['secondary_color','Sekundär'],['accent_color','Akzent']] as [$field, $label])
                    <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3">
                        <label class="mb-1.5 block text-xs font-medium text-[color:var(--nx-text)]">{{ $label }}</label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="ciBoard.{{ $field }}" value="{{ $ciBoard->$field ?: '#000000' }}"
                                   class="h-9 w-10 shrink-0 cursor-pointer rounded-[6px] border border-[color:var(--nx-line-strong)] bg-transparent p-0.5">
                            <input type="text" wire:model.live="ciBoard.{{ $field }}" placeholder="#000000" pattern="^#[0-9A-Fa-f]{6}$"
                                   class="w-full rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-2.5 py-1.5 font-mono text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]">
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Palette (benannte Farben) --}}
            @if($ciBoard->colors->count() > 0)
                <p class="mb-3 mt-6 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Palette</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($ciBoard->colors as $color)
                        <x-nx-card flush class="group overflow-hidden">
                            <div class="h-24" style="background-color: {{ $color->color ?? '#000000' }};"></div>
                            <div class="flex items-start justify-between gap-2 p-3">
                                <div class="min-w-0">
                                    <div class="truncate text-[13px] font-medium text-[color:var(--nx-text)]">{{ $color->title ?: 'Farbe' }}</div>
                                    <div class="text-[11px] uppercase tabular-nums text-[color:var(--nx-faint)]">{{ $color->color ?? '—' }}</div>
                                    @if($color->description)
                                        <div class="mt-1 line-clamp-2 text-[11px] text-[color:var(--nx-faint)]">{{ $color->description }}</div>
                                    @endif
                                </div>
                                @can('update', $ciBoard)
                                    <button type="button" x-data @click="$dispatch('open-modal-ci-board-color', { ciBoardId: {{ $ciBoard->id }}, colorId: {{ $color->id }} })"
                                            class="shrink-0 rounded p-1 text-[color:var(--nx-faint)] opacity-0 transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)] group-hover:opacity-100" title="Bearbeiten">
                                        @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                    </button>
                                @endcan
                            </div>
                        </x-nx-card>
                    @endforeach
                </div>
            @else
                <div class="mt-6">
                    <x-nx-empty icon="heroicon-o-paint-brush">Noch keine Palette-Farben – füge deine erste Markenfarbe hinzu.</x-nx-empty>
                </div>
            @endif
        </x-nx-section>

        {{-- Text & Schrift --}}
        <x-nx-section icon="heroicon-o-language" title="Text & Schrift" description="Slogan, Tagline und Schriftart">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="space-y-4">
                    <x-nx-input-textarea name="ciSlogan" label="Slogan" :rows="4" wire:model="ciBoard.slogan" placeholder="Dein prägnanter Marken-Slogan…" hint="Zentraler Slogan" />
                    <x-nx-input-textarea name="ciTagline" label="Tagline" :rows="3" wire:model="ciBoard.tagline" placeholder="Kurze, prägnante Beschreibung…" hint="Untertitel" />
                </div>
                <div>
                    <x-nx-input-text name="ciFont" label="Schriftart" wire:model="ciBoard.font_family" placeholder="z. B. Inter, Roboto…" hint="Primäre Schrift" />
                    @if($ciBoard->font_family)
                        <div class="mt-4 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-4">
                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Vorschau</p>
                            <p class="text-2xl font-bold text-[color:var(--nx-text)]" style="font-family: {{ $ciBoard->font_family }}, sans-serif;">{{ $ciBoard->name ?: 'Beispieltext' }}</p>
                            <p class="mt-1.5 text-sm text-[color:var(--nx-muted)]" style="font-family: {{ $ciBoard->font_family }}, sans-serif;">{{ $ciBoard->slogan ?: 'Vorschau der Schriftart' }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </x-nx-section>

    </x-ui-page-container>

    <x-slot name="sidebar">
        @include('brands::partials.board-sidebar', ['detailRows' => [
            ['label' => 'Typ', 'value' => 'CI Board'],
            ['label' => 'Erstellt', 'value' => $ciBoard->created_at->format('d.m.Y')],
            ['label' => 'Farben', 'value' => (string) $ciBoard->colors->count()],
        ]])
    </x-slot>

    <x-slot name="activity">
        @include('brands::partials.board-activity')
    </x-slot>

    <livewire:brands.ci-board-settings-modal/>
    <livewire:brands.ci-board-color-modal/>
</x-ui-page>
