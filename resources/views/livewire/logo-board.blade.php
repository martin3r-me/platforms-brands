<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$logoBoard->name" icon="heroicon-o-photo" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $logoBoard->brand->name, 'href' => route('brands.brands.show', $logoBoard->brand)],
            ['label' => $logoBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $logoBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-logo-board-settings', { logoBoardId: {{ $logoBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
            </x-slot>

            @can('update', $logoBoard)
                <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-logo-variant', { logoBoardId: {{ $logoBoard->id }} })">
                    @svg('heroicon-o-plus', 'w-4 h-4') Logo-Variante
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $logoBoard->name }}</h1>
            @if($logoBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $logoBoard->description }}</p>
            @endif
        </div>

        {{-- Logo-Varianten --}}
        <x-nx-section icon="heroicon-o-squares-2x2" title="Logo-Varianten" :hint="(string) $variants->count()" description="Vorschau auf hellem und dunklem Hintergrund">
            @can('update', $logoBoard)
                <x-slot name="action">
                    <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-logo-variant', { logoBoardId: {{ $logoBoard->id }} })">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5') Variante
                    </x-nx-button>
                </x-slot>
            @endcan

            @if($variants->count() > 0)
                <div class="space-y-5">
                    @foreach($variants as $variant)
                        <x-nx-card flush class="group overflow-hidden">
                            {{-- Variant Header --}}
                            <div class="flex items-center justify-between gap-3 border-b border-[color:var(--nx-line)] px-4 py-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <x-nx-badge variant="neutral">{{ $variant->type_label }}</x-nx-badge>
                                    <h3 class="truncate text-[14px] font-medium text-[color:var(--nx-text)]">{{ $variant->name }}</h3>
                                </div>
                                @can('update', $logoBoard)
                                    <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button
                                            x-data
                                            @click="$dispatch('open-modal-logo-variant', { logoBoardId: {{ $logoBoard->id }}, variantId: {{ $variant->id }} })"
                                            class="rounded p-1.5 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]"
                                            title="Bearbeiten"
                                        >
                                            @svg('heroicon-o-pencil', 'w-4 h-4')
                                        </button>
                                        <button
                                            wire:click="deleteVariant({{ $variant->id }})"
                                            wire:confirm="Logo-Variante wirklich löschen?"
                                            class="rounded p-1.5 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-danger)]"
                                            title="Löschen"
                                        >
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                        </button>
                                    </div>
                                @endcan
                            </div>

                            {{-- Light / Dark Background Preview --}}
                            <div class="grid grid-cols-1 lg:grid-cols-2">
                                {{-- Light Background --}}
                                <div class="flex min-h-[200px] flex-col items-center justify-center border-b border-[color:var(--nx-line)] bg-white p-8 lg:border-b-0 lg:border-r">
                                    <div class="mb-4 text-[10px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Heller Hintergrund</div>
                                    @if($variant->file_path)
                                        <div class="relative">
                                            @if($variant->is_svg)
                                                <img src="{{ $variant->file_url }}" alt="{{ $variant->name }}" class="max-w-[200px] max-h-[120px] object-contain">
                                            @else
                                                <img src="{{ $variant->file_url }}" alt="{{ $variant->name }}" class="max-w-[200px] max-h-[120px] object-contain">
                                            @endif
                                        </div>
                                    @else
                                        <div class="flex h-20 w-32 items-center justify-center rounded-[8px] border border-dashed border-[color:var(--nx-line-strong)]">
                                            @svg('heroicon-o-photo', 'w-8 h-8 text-[color:var(--nx-faint)]')
                                        </div>
                                        <p class="mt-2 text-xs text-[color:var(--nx-faint)]">Kein Logo hochgeladen</p>
                                    @endif
                                </div>

                                {{-- Dark Background --}}
                                <div class="flex min-h-[200px] flex-col items-center justify-center bg-gray-900 p-8">
                                    <div class="mb-4 text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-400">Dunkler Hintergrund</div>
                                    @if($variant->file_path)
                                        <div class="relative">
                                            <img src="{{ $variant->file_url }}" alt="{{ $variant->name }}" class="max-w-[200px] max-h-[120px] object-contain">
                                        </div>
                                    @else
                                        <div class="flex h-20 w-32 items-center justify-center rounded-[8px] border border-dashed border-gray-600">
                                            @svg('heroicon-o-photo', 'w-8 h-8 text-gray-500')
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500">Kein Logo hochgeladen</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Protection Zone & Min Sizes Visualization --}}
                            @if($variant->clearspace_factor || $variant->min_width_px || $variant->min_width_mm)
                                <div class="border-t border-[color:var(--nx-line)] px-4 py-5">
                                    <div class="mb-4 flex items-center gap-2">
                                        @svg('heroicon-o-shield-check', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                        <h4 class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Schutzzonen &amp; Mindestgrößen</h4>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        @if($variant->clearspace_factor)
                                            <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-4">
                                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Schutzzone</div>
                                                <div class="mb-3 flex items-center justify-center">
                                                    <div class="relative">
                                                        <div class="rounded-[6px] border border-dashed border-[color:var(--nx-line-strong)] bg-[color:var(--nx-hover)] p-4">
                                                            <div class="flex h-10 w-16 items-center justify-center rounded bg-[color:var(--nx-hover)]">
                                                                @svg('heroicon-o-photo', 'w-6 h-6 text-[color:var(--nx-faint)]')
                                                            </div>
                                                        </div>
                                                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 transform">
                                                            <span class="rounded border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-1 text-[10px] font-bold text-[color:var(--nx-text)]">{{ $variant->clearspace_factor }}x</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-center text-sm text-[color:var(--nx-text)]">
                                                    <span class="font-medium">{{ $variant->clearspace_factor }}x</span> der Logohöhe
                                                </div>
                                            </div>
                                        @endif

                                        @if($variant->min_width_px)
                                            <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-4">
                                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Mindestbreite (Digital)</div>
                                                <div class="mb-3 flex items-center justify-center">
                                                    <div class="flex items-end gap-1">
                                                        <div class="flex h-5 w-8 items-center justify-center rounded border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-hover)]">
                                                            @svg('heroicon-o-photo', 'w-3 h-3 text-[color:var(--nx-faint)]')
                                                        </div>
                                                        @svg('heroicon-o-arrow-right', 'w-3 h-3 text-[color:var(--nx-faint)]')
                                                        <div class="flex h-7 w-12 items-center justify-center rounded border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-hover)]">
                                                            @svg('heroicon-o-photo', 'w-4 h-4 text-[color:var(--nx-muted)]')
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-center text-sm text-[color:var(--nx-text)]">
                                                    min. <span class="font-medium">{{ $variant->min_width_px }}px</span>
                                                </div>
                                            </div>
                                        @endif

                                        @if($variant->min_width_mm)
                                            <div class="rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-4">
                                                <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Mindestbreite (Print)</div>
                                                <div class="mb-3 flex items-center justify-center">
                                                    <div class="flex items-end gap-1">
                                                        <div class="flex h-5 w-8 items-center justify-center rounded border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-hover)]">
                                                            @svg('heroicon-o-printer', 'w-3 h-3 text-[color:var(--nx-faint)]')
                                                        </div>
                                                        @svg('heroicon-o-arrow-right', 'w-3 h-3 text-[color:var(--nx-faint)]')
                                                        <div class="flex h-7 w-12 items-center justify-center rounded border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-hover)]">
                                                            @svg('heroicon-o-printer', 'w-4 h-4 text-[color:var(--nx-muted)]')
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-center text-sm text-[color:var(--nx-text)]">
                                                    min. <span class="font-medium">{{ $variant->min_width_mm }}mm</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- File Formats Info --}}
                            @if($variant->file_path || ($variant->additional_formats && count($variant->additional_formats) > 0))
                                <div class="border-t border-[color:var(--nx-line)] px-4 py-4">
                                    <div class="mb-3 flex items-center gap-2">
                                        @svg('heroicon-o-document-duplicate', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                        <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Verfügbare Formate</span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @if($variant->file_format)
                                            <x-nx-badge variant="accent">
                                                @svg('heroicon-o-document', 'w-3.5 h-3.5')
                                                {{ strtoupper($variant->file_format) }} (Haupt)
                                            </x-nx-badge>
                                        @endif
                                        @if($variant->additional_formats)
                                            @foreach($variant->additional_formats as $format)
                                                <x-nx-badge variant="neutral">
                                                    @svg('heroicon-o-document', 'w-3.5 h-3.5')
                                                    {{ strtoupper($format['format'] ?? 'FILE') }}
                                                    @if(isset($format['width']) && isset($format['height']))
                                                        ({{ $format['width'] }}&times;{{ $format['height'] }})
                                                    @endif
                                                </x-nx-badge>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Usage Guidelines --}}
                            @if($variant->usage_guidelines)
                                <div class="border-t border-[color:var(--nx-line)] px-4 py-4">
                                    <div class="mb-2 flex items-center gap-2">
                                        @svg('heroicon-o-information-circle', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                        <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Verwendungsrichtlinien</span>
                                    </div>
                                    <p class="text-sm text-[color:var(--nx-text)]">{{ $variant->usage_guidelines }}</p>
                                </div>
                            @endif

                            {{-- Do's & Don'ts --}}
                            @if(($variant->dos && count($variant->dos) > 0) || ($variant->donts && count($variant->donts) > 0))
                                <div class="border-t border-[color:var(--nx-line)] px-4 py-5">
                                    <div class="mb-4 flex items-center gap-2">
                                        @svg('heroicon-o-hand-thumb-up', 'w-4 h-4 text-[color:var(--nx-faint)]')
                                        <h4 class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Do's &amp; Don'ts</h4>
                                    </div>
                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        {{-- Do's --}}
                                        @if($variant->dos && count($variant->dos) > 0)
                                            <div>
                                                <div class="mb-3 flex items-center gap-2">
                                                    <x-nx-badge variant="success" dot>Do's</x-nx-badge>
                                                </div>
                                                <div class="space-y-2">
                                                    @foreach($variant->dos as $do)
                                                        <div class="flex items-start gap-2 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3">
                                                            @svg('heroicon-o-check-circle', 'w-4 h-4 text-[color:var(--nx-success)] mt-0.5 flex-shrink-0')
                                                            <span class="text-sm text-[color:var(--nx-text)]">{{ $do['text'] ?? '' }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Don'ts --}}
                                        @if($variant->donts && count($variant->donts) > 0)
                                            <div>
                                                <div class="mb-3 flex items-center gap-2">
                                                    <x-nx-badge variant="danger" dot>Don'ts</x-nx-badge>
                                                </div>
                                                <div class="space-y-2">
                                                    @foreach($variant->donts as $dont)
                                                        <div class="flex items-start gap-2 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3">
                                                            @svg('heroicon-o-x-circle', 'w-4 h-4 text-[color:var(--nx-danger)] mt-0.5 flex-shrink-0')
                                                            <span class="text-sm text-[color:var(--nx-text)]">{{ $dont['text'] ?? '' }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Description --}}
                            @if($variant->description)
                                <div class="border-t border-[color:var(--nx-line)] px-4 py-4">
                                    <p class="text-sm text-[color:var(--nx-muted)]">{{ $variant->description }}</p>
                                </div>
                            @endif
                        </x-nx-card>
                    @endforeach
                </div>
            @else
                <x-nx-empty icon="heroicon-o-photo">
                    Noch keine Logo-Varianten – erstelle deine erste Logo-Variante.
                    @can('update', $logoBoard)
                        <x-slot name="action">
                            <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-logo-variant', { logoBoardId: {{ $logoBoard->id }} })">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5') Logo-Variante hinzufügen
                            </x-nx-button>
                        </x-slot>
                    @endcan
                </x-nx-empty>
            @endif
        </x-nx-section>

    </x-ui-page-container>

    <x-slot name="sidebar">
        @php
            $logoDetailRows = [
                ['label' => 'Typ', 'value' => 'Logo Board'],
                ['label' => 'Erstellt', 'value' => $logoBoard->created_at->format('d.m.Y')],
            ];
            if ($variants->count() > 0) {
                $logoDetailRows[] = ['label' => 'Varianten', 'value' => (string) $variants->count()];
                foreach ($variants->groupBy('type') as $type => $group) {
                    $logoDetailRows[] = [
                        'label' => \Platform\Brands\Models\BrandsLogoVariant::TYPES[$type] ?? $type,
                        'value' => (string) $group->count(),
                    ];
                }
            }
        @endphp
        @include('brands::partials.board-sidebar', ['detailRows' => $logoDetailRows])
    </x-slot>

    <x-slot name="activity">
        @include('brands::partials.board-activity')
    </x-slot>

    <livewire:brands.logo-board-settings-modal />
    <livewire:brands.logo-variant-modal />
</x-ui-page>
