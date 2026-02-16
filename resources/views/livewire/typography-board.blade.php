<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$typographyBoard->name" icon="heroicon-o-language">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $typographyBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
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
                        @svg('heroicon-o-language', 'w-6 h-6 text-rose-600')
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">{{ $typographyBoard->name }}</h1>
                        @if($typographyBoard->description)
                            <p class="text-[var(--ui-muted)] mt-1">{{ $typographyBoard->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Hierarchy Cascade Section --}}
        <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-rose-100 to-rose-50 flex items-center justify-center">
                            @svg('heroicon-o-bars-3-bottom-left', 'w-5 h-5 text-rose-600')
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Typografie-Hierarchie</h2>
                            <p class="text-sm text-[var(--ui-muted)]">Schrift-Definitionen mit Live-Vorschau</p>
                        </div>
                    </div>
                    @can('update', $typographyBoard)
                        <x-ui-button
                            variant="primary"
                            size="sm"
                            x-data
                            @click="$dispatch('open-modal-typography-entry', { typographyBoardId: {{ $typographyBoard->id }} })"
                        >
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Schrift hinzufügen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>

                @if($entries->count() > 0)
                    {{-- Hierarchy Cascade: Visual staircase from large to small --}}
                    <div class="space-y-0">
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

                            <div class="group relative border-b border-[var(--ui-border)]/40 last:border-b-0 hover:bg-[var(--ui-muted-5)] transition-colors">
                                <div class="flex items-start gap-6 py-5 px-4">
                                    {{-- Role Badge --}}
                                    <div class="flex-shrink-0 w-20 pt-1">
                                        @if($entry->role)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 text-xs font-bold uppercase tracking-wider border border-rose-200">
                                                {{ strtoupper($entry->role) }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-gray-50 text-gray-500 text-xs font-medium border border-gray-200">
                                                Custom
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Live Preview --}}
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="text-[var(--ui-secondary)] truncate"
                                            style="{{ $entry->preview_style }}"
                                        >
                                            {{ $sampleText }}
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-[var(--ui-muted)]">
                                            <span class="font-medium text-[var(--ui-secondary)]">{{ $entry->name }}</span>
                                            <span class="text-[var(--ui-border)]">|</span>
                                            <span>{{ $entry->font_family }}</span>
                                            <span class="text-[var(--ui-border)]">|</span>
                                            <span>{{ $entry->weight_label }} ({{ $entry->font_weight }})</span>
                                            <span class="text-[var(--ui-border)]">|</span>
                                            <span>{{ $entry->font_size }}px</span>
                                            @if($entry->line_height)
                                                <span class="text-[var(--ui-border)]">|</span>
                                                <span>LH: {{ $entry->line_height }}</span>
                                            @endif
                                            @if($entry->letter_spacing !== null)
                                                <span class="text-[var(--ui-border)]">|</span>
                                                <span>LS: {{ $entry->letter_spacing }}px</span>
                                            @endif
                                            @if($entry->font_source === 'google')
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-medium">Google Fonts</span>
                                            @elseif($entry->font_source === 'custom')
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-purple-50 text-purple-600 text-[10px] font-medium">Custom Font</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Actions --}}
                                    @can('update', $typographyBoard)
                                        <div class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                x-data
                                                @click="$dispatch('open-modal-typography-entry', { typographyBoardId: {{ $typographyBoard->id }}, entryId: {{ $entry->id }} })"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-[var(--ui-primary-5)] rounded transition-colors"
                                                title="Bearbeiten"
                                            >
                                                @svg('heroicon-o-pencil', 'w-4 h-4')
                                            </button>
                                            <button
                                                wire:click="deleteEntry({{ $entry->id }})"
                                                wire:confirm="Schrift-Definition wirklich löschen?"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                                                title="Löschen"
                                            >
                                                @svg('heroicon-o-trash', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-rose-50 mb-4">
                            @svg('heroicon-o-language', 'w-8 h-8 text-rose-400')
                        </div>
                        <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Schrift-Definitionen</p>
                        <p class="text-xs text-[var(--ui-muted)] mb-4">Erstelle deine erste typografische Definition</p>
                        @can('update', $typographyBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-typography-entry', { typographyBoardId: {{ $typographyBoard->id }} })"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Schrift hinzufügen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                @endif
            </div>
        </div>

        {{-- Font Pairing Preview --}}
        @if($entries->count() >= 2)
            @php
                $headlineEntry = $entries->first(fn($e) => in_array($e->role, ['h1', 'h2', 'h3'])) ?? $entries->first();
                $bodyEntry = $entries->first(fn($e) => in_array($e->role, ['body', 'body-sm'])) ?? $entries->skip(1)->first();
            @endphp
            <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                <div class="p-6 lg:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-100 to-indigo-50 flex items-center justify-center">
                            @svg('heroicon-o-document-duplicate', 'w-5 h-5 text-indigo-600')
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Font-Pairing Vorschau</h2>
                            <p class="text-sm text-[var(--ui-muted)]">Headline + Body im Zusammenspiel</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {{-- Pairing Preview --}}
                        <div class="bg-[var(--ui-muted-5)] rounded-xl p-8 border border-[var(--ui-border)]/40">
                            <div style="{{ $headlineEntry->preview_style }}" class="text-[var(--ui-secondary)] mb-4">
                                {{ $headlineEntry->sample_text ?: 'Überschrift Beispiel' }}
                            </div>
                            <div style="{{ $bodyEntry->preview_style }}" class="text-[var(--ui-secondary)]">
                                {{ $bodyEntry->sample_text ?: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.' }}
                            </div>
                        </div>

                        {{-- Pairing Info --}}
                        <div class="space-y-4">
                            <div class="p-4 bg-[var(--ui-muted-5)] rounded-xl border border-[var(--ui-border)]/40">
                                <div class="text-xs font-semibold text-[var(--ui-muted)] mb-2 uppercase tracking-wider">Headline Font</div>
                                <div class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $headlineEntry->font_family }}</div>
                                <div class="text-sm text-[var(--ui-muted)]">{{ $headlineEntry->weight_label }} · {{ $headlineEntry->font_size }}px</div>
                            </div>
                            <div class="p-4 bg-[var(--ui-muted-5)] rounded-xl border border-[var(--ui-border)]/40">
                                <div class="text-xs font-semibold text-[var(--ui-muted)] mb-2 uppercase tracking-wider">Body Font</div>
                                <div class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $bodyEntry->font_family }}</div>
                                <div class="text-sm text-[var(--ui-muted)]">{{ $bodyEntry->weight_label }} · {{ $bodyEntry->font_size }}px</div>
                            </div>
                        </div>
                    </div>
                </div>
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
                        <a href="{{ route('brands.brands.show', $typographyBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurück zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $typographyBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-typography-entry', { typographyBoardId: {{ $typographyBoard->id }} })"
                                class="w-full"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Schrift hinzufügen</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-typography-board-settings', { typographyBoardId: {{ $typographyBoard->id }} })" class="w-full">
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
                                Typografie Board
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $typographyBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($entries->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Schriften</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $entries->count() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Font Families Overview --}}
                @if($entries->count() > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Verwendete Schriften</h3>
                        <div class="space-y-2">
                            @foreach($entries->unique('font_family') as $entry)
                                <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $entry->font_family }}</span>
                                    @if($entry->font_source === 'google')
                                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-blue-50 text-blue-600">Google</span>
                                    @elseif($entry->font_source === 'custom')
                                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-purple-50 text-purple-600">Custom</span>
                                    @else
                                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-gray-50 text-gray-500">System</span>
                                    @endif
                                </div>
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

    <livewire:brands.typography-board-settings-modal />
    <livewire:brands.typography-entry-modal />
</x-ui-page>
