<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$typographyBoard->name" icon="heroicon-o-language" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $typographyBoard->brand->name, 'href' => route('brands.brands.show', $typographyBoard->brand)],
            ['label' => $typographyBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $typographyBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-typography-board-settings', { typographyBoardId: {{ $typographyBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
            </x-slot>

            @can('update', $typographyBoard)
                <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-typography-entry', { typographyBoardId: {{ $typographyBoard->id }} })">
                    @svg('heroicon-o-plus', 'w-4 h-4') Schrift hinzufügen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">
        {{-- Self-hosted Katalog-Fonts (lädt nur, was gerendert wird) --}}
        @include('brands::partials.fonts')

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $typographyBoard->name }}</h1>
            @if($typographyBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $typographyBoard->description }}</p>
            @endif
        </div>

        {{-- Typografie-Hierarchie --}}
        <x-nx-section icon="heroicon-o-bars-3-bottom-left" title="Typografie-Hierarchie" :hint="$entries->count()" description="Schrift-Definitionen mit Live-Vorschau">
            @can('update', $typographyBoard)
                <x-slot name="action">
                    <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-typography-entry', { typographyBoardId: {{ $typographyBoard->id }} })">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5') Schrift
                    </x-nx-button>
                </x-slot>
            @endcan

            @if($entries->count() > 0)
                {{-- Hierarchy Cascade: rahmenlose Hairline-Liste, groß nach klein --}}
                <x-nx-card flush class="divide-y divide-[color:var(--nx-line)]">
                    @foreach($entries as $entry)
                        @php
                            $googleFontUrl = null;
                            if ($entry->font_source === 'google') {
                                $fontFamily = str_replace(' ', '+', $entry->font_family);
                                $googleFontUrl = "https://fonts.googleapis.com/css2?family={$fontFamily}:wght@{$entry->font_weight}&display=swap";
                            }
                            $customFontUrl = null;
                            if ($entry->font_source === 'custom' && $entry->font_file_path) {
                                $customFontUrl = asset('storage/' . $entry->font_file_path);
                            }
                            $sampleText = $entry->sample_text ?: ($entry->role_label ?? $entry->name);
                        @endphp

                        {{-- Load Google Font if needed --}}
                        @if($googleFontUrl)
                            <link href="{{ $googleFontUrl }}" rel="stylesheet">
                        @endif

                        {{-- Custom Font Face --}}
                        @if($customFontUrl)
                            <style>
                                @font-face {
                                    font-family: '{{ $entry->font_family }}';
                                    src: url('{{ $customFontUrl }}') format('{{ pathinfo($entry->font_file_path, PATHINFO_EXTENSION) === "woff2" ? "woff2" : (pathinfo($entry->font_file_path, PATHINFO_EXTENSION) === "ttf" ? "truetype" : "opentype") }}');
                                    font-weight: {{ $entry->font_weight }};
                                    font-style: {{ $entry->font_style }};
                                    font-display: swap;
                                }
                            </style>
                        @endif

                        <div class="group relative transition-colors hover:bg-[color:var(--nx-hover)]">
                            <div class="flex items-start gap-6 px-4 py-5">
                                {{-- Role Badge --}}
                                <div class="w-20 shrink-0 pt-1">
                                    @if($entry->role)
                                        <x-nx-badge variant="accent">{{ strtoupper($entry->role) }}</x-nx-badge>
                                    @else
                                        <x-nx-badge variant="neutral">Custom</x-nx-badge>
                                    @endif
                                </div>

                                {{-- Live Preview --}}
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="truncate text-[color:var(--nx-text)]"
                                        style="{{ $entry->preview_style }}"
                                    >
                                        {{ $sampleText }}
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center gap-3 text-[11px] text-[color:var(--nx-faint)]">
                                        <span class="font-medium text-[color:var(--nx-text)]">{{ $entry->name }}</span>
                                        <span class="text-[color:var(--nx-line-strong)]">|</span>
                                        <span>{{ $entry->font_family }}</span>
                                        <span class="text-[color:var(--nx-line-strong)]">|</span>
                                        <span>{{ $entry->weight_label }} ({{ $entry->font_weight }})</span>
                                        <span class="text-[color:var(--nx-line-strong)]">|</span>
                                        <span>{{ $entry->font_size }}px</span>
                                        @if($entry->line_height)
                                            <span class="text-[color:var(--nx-line-strong)]">|</span>
                                            <span>LH: {{ $entry->line_height }}</span>
                                        @endif
                                        @if($entry->letter_spacing !== null)
                                            <span class="text-[color:var(--nx-line-strong)]">|</span>
                                            <span>LS: {{ $entry->letter_spacing }}px</span>
                                        @endif
                                        @if($entry->font_source === 'google')
                                            <x-nx-badge variant="info">Google Fonts</x-nx-badge>
                                        @elseif($entry->font_source === 'custom')
                                            <x-nx-badge variant="accent">Custom Font</x-nx-badge>
                                        @endif
                                    </div>
                                </div>

                                {{-- Actions --}}
                                @can('update', $typographyBoard)
                                    <div class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button
                                            x-data
                                            @click="$dispatch('open-modal-typography-entry', { typographyBoardId: {{ $typographyBoard->id }}, entryId: {{ $entry->id }} })"
                                            class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]"
                                            title="Bearbeiten"
                                        >
                                            @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                        </button>
                                        <button
                                            wire:click="deleteEntry({{ $entry->id }})"
                                            wire:confirm="Schrift-Definition wirklich löschen?"
                                            class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-danger)]"
                                            title="Löschen"
                                        >
                                            @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                                        </button>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </x-nx-card>
            @else
                <x-nx-empty icon="heroicon-o-language">
                    Noch keine Schrift-Definitionen – erstelle deine erste typografische Definition.
                    @can('update', $typographyBoard)
                        <x-slot name="action">
                            <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-typography-entry', { typographyBoardId: {{ $typographyBoard->id }} })">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5') Schrift hinzufügen
                            </x-nx-button>
                        </x-slot>
                    @endcan
                </x-nx-empty>
            @endif
        </x-nx-section>

        {{-- Font-Pairing Vorschau --}}
        @if($entries->count() >= 2)
            @php
                $headlineEntry = $entries->first(fn($e) => in_array($e->role, ['h1', 'h2', 'h3'])) ?? $entries->first();
                $bodyEntry = $entries->first(fn($e) => in_array($e->role, ['body', 'body-sm'])) ?? $entries->skip(1)->first();
            @endphp
            <x-nx-section icon="heroicon-o-document-duplicate" title="Font-Pairing Vorschau" description="Headline + Body im Zusammenspiel">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {{-- Pairing Preview --}}
                    <x-nx-card>
                        <div style="{{ $headlineEntry->preview_style }}" class="mb-4 text-[color:var(--nx-text)]">
                            {{ $headlineEntry->sample_text ?: 'Überschrift Beispiel' }}
                        </div>
                        <div style="{{ $bodyEntry->preview_style }}" class="text-[color:var(--nx-text)]">
                            {{ $bodyEntry->sample_text ?: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.' }}
                        </div>
                    </x-nx-card>

                    {{-- Pairing Info --}}
                    <div class="space-y-3">
                        <x-nx-card>
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Headline Font</div>
                            <div class="text-[15px] font-semibold text-[color:var(--nx-text)]">{{ $headlineEntry->font_family }}</div>
                            <div class="text-[13px] text-[color:var(--nx-muted)]">{{ $headlineEntry->weight_label }} · {{ $headlineEntry->font_size }}px</div>
                        </x-nx-card>
                        <x-nx-card>
                            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Body Font</div>
                            <div class="text-[15px] font-semibold text-[color:var(--nx-text)]">{{ $bodyEntry->font_family }}</div>
                            <div class="text-[13px] text-[color:var(--nx-muted)]">{{ $bodyEntry->weight_label }} · {{ $bodyEntry->font_size }}px</div>
                        </x-nx-card>
                    </div>
                </div>
            </x-nx-section>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        @include('brands::partials.board-sidebar', ['detailRows' => array_values(array_filter([
            ['label' => 'Typ', 'value' => 'Typografie Board'],
            ['label' => 'Erstellt', 'value' => $typographyBoard->created_at->format('d.m.Y')],
            $entries->count() > 0 ? ['label' => 'Schriften', 'value' => (string) $entries->count()] : null,
        ]))])
    </x-slot>

    <x-slot name="activity">
        @include('brands::partials.board-activity')
    </x-slot>

    <livewire:brands.typography-board-settings-modal />
    <livewire:brands.typography-entry-modal />
</x-ui-page>
