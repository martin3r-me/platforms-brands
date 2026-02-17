<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$logoBoard->name" icon="heroicon-o-photo">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $logoBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
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
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center">
                        @svg('heroicon-o-photo', 'w-6 h-6 text-emerald-600')
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">{{ $logoBoard->name }}</h1>
                        @if($logoBoard->description)
                            <p class="text-[var(--ui-muted)] mt-1">{{ $logoBoard->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Logo Gallery Section --}}
        <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center">
                            @svg('heroicon-o-squares-2x2', 'w-5 h-5 text-emerald-600')
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Logo-Varianten</h2>
                            <p class="text-sm text-[var(--ui-muted)]">Alle Varianten mit Vorschau auf hellem und dunklem Hintergrund</p>
                        </div>
                    </div>
                    @can('update', $logoBoard)
                        <x-ui-button
                            variant="primary"
                            size="sm"
                            x-data
                            @click="$dispatch('open-modal-logo-variant', { logoBoardId: {{ $logoBoard->id }} })"
                        >
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Logo-Variante hinzufügen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>

                @if($variants->count() > 0)
                    <div class="space-y-8">
                        @foreach($variants as $variant)
                            <div class="border border-[var(--ui-border)]/40 rounded-xl overflow-hidden group">
                                {{-- Variant Header --}}
                                <div class="px-6 py-4 bg-[var(--ui-muted-5)] border-b border-[var(--ui-border)]/40 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 text-xs font-bold uppercase tracking-wider border border-emerald-200">
                                            {{ $variant->type_label }}
                                        </span>
                                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $variant->name }}</h3>
                                    </div>
                                    @can('update', $logoBoard)
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                x-data
                                                @click="$dispatch('open-modal-logo-variant', { logoBoardId: {{ $logoBoard->id }}, variantId: {{ $variant->id }} })"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-[var(--ui-primary-5)] rounded transition-colors"
                                                title="Bearbeiten"
                                            >
                                                @svg('heroicon-o-pencil', 'w-4 h-4')
                                            </button>
                                            <button
                                                wire:click="deleteVariant({{ $variant->id }})"
                                                wire:confirm="Logo-Variante wirklich löschen?"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded transition-colors"
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
                                    <div class="p-8 flex flex-col items-center justify-center min-h-[200px] bg-white border-r border-[var(--ui-border)]/40">
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Heller Hintergrund</div>
                                        @if($variant->file_path)
                                            <div class="relative">
                                                @if($variant->is_svg)
                                                    <img src="{{ $variant->file_url }}" alt="{{ $variant->name }}" class="max-w-[200px] max-h-[120px] object-contain">
                                                @else
                                                    <img src="{{ $variant->file_url }}" alt="{{ $variant->name }}" class="max-w-[200px] max-h-[120px] object-contain">
                                                @endif
                                            </div>
                                        @else
                                            <div class="flex items-center justify-center w-32 h-20 border-2 border-dashed border-[var(--ui-border)]/60 rounded-lg">
                                                @svg('heroicon-o-photo', 'w-8 h-8 text-[var(--ui-muted)]')
                                            </div>
                                            <p class="text-xs text-[var(--ui-muted)] mt-2">Kein Logo hochgeladen</p>
                                        @endif
                                    </div>

                                    {{-- Dark Background --}}
                                    <div class="p-8 flex flex-col items-center justify-center min-h-[200px] bg-gray-900">
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-4">Dunkler Hintergrund</div>
                                        @if($variant->file_path)
                                            <div class="relative">
                                                <img src="{{ $variant->file_url }}" alt="{{ $variant->name }}" class="max-w-[200px] max-h-[120px] object-contain">
                                            </div>
                                        @else
                                            <div class="flex items-center justify-center w-32 h-20 border-2 border-dashed border-gray-600 rounded-lg">
                                                @svg('heroicon-o-photo', 'w-8 h-8 text-gray-500')
                                            </div>
                                            <p class="text-xs text-gray-500 mt-2">Kein Logo hochgeladen</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Protection Zone & Min Sizes Visualization --}}
                                @if($variant->clearspace_factor || $variant->min_width_px || $variant->min_width_mm)
                                    <div class="px-6 py-5 border-t border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                                        <div class="flex items-center gap-2 mb-4">
                                            @svg('heroicon-o-shield-check', 'w-5 h-5 text-blue-600')
                                            <h4 class="text-sm font-semibold text-[var(--ui-secondary)]">Schutzzonen & Mindestgrößen</h4>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            @if($variant->clearspace_factor)
                                                <div class="bg-white rounded-lg p-4 border border-[var(--ui-border)]/40">
                                                    <div class="text-xs font-semibold text-[var(--ui-muted)] mb-2 uppercase tracking-wider">Schutzzone</div>
                                                    {{-- Visual clearspace indicator --}}
                                                    <div class="flex items-center justify-center mb-3">
                                                        <div class="relative">
                                                            {{-- Clearspace border visualization --}}
                                                            <div class="border-2 border-dashed border-blue-300 rounded p-4 bg-blue-50/30">
                                                                <div class="w-16 h-10 bg-gray-300 rounded flex items-center justify-center">
                                                                    @svg('heroicon-o-photo', 'w-6 h-6 text-gray-500')
                                                                </div>
                                                            </div>
                                                            {{-- Clearspace arrows --}}
                                                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                                                                <span class="text-[10px] font-bold text-blue-600 bg-white px-1 border border-blue-200 rounded">{{ $variant->clearspace_factor }}x</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-center text-sm text-[var(--ui-secondary)]">
                                                        <span class="font-medium">{{ $variant->clearspace_factor }}x</span> der Logohöhe
                                                    </div>
                                                </div>
                                            @endif

                                            @if($variant->min_width_px)
                                                <div class="bg-white rounded-lg p-4 border border-[var(--ui-border)]/40">
                                                    <div class="text-xs font-semibold text-[var(--ui-muted)] mb-2 uppercase tracking-wider">Mindestbreite (Digital)</div>
                                                    <div class="flex items-center justify-center mb-3">
                                                        <div class="flex items-end gap-1">
                                                            <div class="w-8 h-5 bg-emerald-100 border border-emerald-300 rounded flex items-center justify-center">
                                                                @svg('heroicon-o-photo', 'w-3 h-3 text-emerald-500')
                                                            </div>
                                                            @svg('heroicon-o-arrow-right', 'w-3 h-3 text-[var(--ui-muted)]')
                                                            <div class="w-12 h-7 bg-emerald-200 border border-emerald-400 rounded flex items-center justify-center">
                                                                @svg('heroicon-o-photo', 'w-4 h-4 text-emerald-600')
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-center text-sm text-[var(--ui-secondary)]">
                                                        min. <span class="font-medium">{{ $variant->min_width_px }}px</span>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($variant->min_width_mm)
                                                <div class="bg-white rounded-lg p-4 border border-[var(--ui-border)]/40">
                                                    <div class="text-xs font-semibold text-[var(--ui-muted)] mb-2 uppercase tracking-wider">Mindestbreite (Print)</div>
                                                    <div class="flex items-center justify-center mb-3">
                                                        <div class="flex items-end gap-1">
                                                            <div class="w-8 h-5 bg-amber-100 border border-amber-300 rounded flex items-center justify-center">
                                                                @svg('heroicon-o-printer', 'w-3 h-3 text-amber-500')
                                                            </div>
                                                            @svg('heroicon-o-arrow-right', 'w-3 h-3 text-[var(--ui-muted)]')
                                                            <div class="w-12 h-7 bg-amber-200 border border-amber-400 rounded flex items-center justify-center">
                                                                @svg('heroicon-o-printer', 'w-4 h-4 text-amber-600')
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-center text-sm text-[var(--ui-secondary)]">
                                                        min. <span class="font-medium">{{ $variant->min_width_mm }}mm</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- File Formats Info --}}
                                @if($variant->file_path || ($variant->additional_formats && count($variant->additional_formats) > 0))
                                    <div class="px-6 py-4 border-t border-[var(--ui-border)]/40">
                                        <div class="flex items-center gap-2 mb-3">
                                            @svg('heroicon-o-document-duplicate', 'w-4 h-4 text-[var(--ui-muted)]')
                                            <span class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wider">Verfügbare Formate</span>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            @if($variant->file_format)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-medium border border-emerald-200">
                                                    @svg('heroicon-o-document', 'w-3.5 h-3.5')
                                                    {{ strtoupper($variant->file_format) }}
                                                    <span class="text-emerald-500">(Haupt)</span>
                                                </span>
                                            @endif
                                            @if($variant->additional_formats)
                                                @foreach($variant->additional_formats as $format)
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-50 text-gray-700 text-xs font-medium border border-gray-200">
                                                        @svg('heroicon-o-document', 'w-3.5 h-3.5')
                                                        {{ strtoupper($format['format'] ?? 'FILE') }}
                                                        @if(isset($format['width']) && isset($format['height']))
                                                            <span class="text-gray-500">({{ $format['width'] }}&times;{{ $format['height'] }})</span>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Usage Guidelines --}}
                                @if($variant->usage_guidelines)
                                    <div class="px-6 py-4 border-t border-[var(--ui-border)]/40">
                                        <div class="flex items-center gap-2 mb-2">
                                            @svg('heroicon-o-information-circle', 'w-4 h-4 text-blue-500')
                                            <span class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wider">Verwendungsrichtlinien</span>
                                        </div>
                                        <p class="text-sm text-[var(--ui-secondary)]">{{ $variant->usage_guidelines }}</p>
                                    </div>
                                @endif

                                {{-- Do's & Don'ts --}}
                                @if(($variant->dos && count($variant->dos) > 0) || ($variant->donts && count($variant->donts) > 0))
                                    <div class="px-6 py-5 border-t border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                                        <div class="flex items-center gap-2 mb-4">
                                            @svg('heroicon-o-hand-thumb-up', 'w-5 h-5 text-emerald-600')
                                            <h4 class="text-sm font-semibold text-[var(--ui-secondary)]">Do's & Don'ts</h4>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            {{-- Do's --}}
                                            @if($variant->dos && count($variant->dos) > 0)
                                                <div>
                                                    <div class="flex items-center gap-2 mb-3">
                                                        <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center">
                                                            @svg('heroicon-o-check', 'w-4 h-4 text-emerald-600')
                                                        </div>
                                                        <span class="text-sm font-semibold text-emerald-700">Do's</span>
                                                    </div>
                                                    <div class="space-y-2">
                                                        @foreach($variant->dos as $do)
                                                            <div class="flex items-start gap-2 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                                                @svg('heroicon-o-check-circle', 'w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0')
                                                                <span class="text-sm text-emerald-800">{{ $do['text'] ?? '' }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- Don'ts --}}
                                            @if($variant->donts && count($variant->donts) > 0)
                                                <div>
                                                    <div class="flex items-center gap-2 mb-3">
                                                        <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center">
                                                            @svg('heroicon-o-x-mark', 'w-4 h-4 text-red-600')
                                                        </div>
                                                        <span class="text-sm font-semibold text-red-700">Don'ts</span>
                                                    </div>
                                                    <div class="space-y-2">
                                                        @foreach($variant->donts as $dont)
                                                            <div class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                                                @svg('heroicon-o-x-circle', 'w-4 h-4 text-red-500 mt-0.5 flex-shrink-0')
                                                                <span class="text-sm text-red-800">{{ $dont['text'] ?? '' }}</span>
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
                                    <div class="px-6 py-4 border-t border-[var(--ui-border)]/40">
                                        <p class="text-sm text-[var(--ui-muted)]">{{ $variant->description }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-50 mb-4">
                            @svg('heroicon-o-photo', 'w-8 h-8 text-emerald-400')
                        </div>
                        <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Logo-Varianten</p>
                        <p class="text-xs text-[var(--ui-muted)] mb-4">Erstelle deine erste Logo-Variante</p>
                        @can('update', $logoBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-logo-variant', { logoBoardId: {{ $logoBoard->id }} })"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Logo-Variante hinzufügen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $logoBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurück zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $logoBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-logo-variant', { logoBoardId: {{ $logoBoard->id }} })"
                                class="w-full"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Logo-Variante hinzufügen</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-logo-board-settings', { logoBoardId: {{ $logoBoard->id }} })" class="w-full">
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
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200">
                                Logo Board
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $logoBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($variants->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Varianten</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $variants->count() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Variant Types Overview --}}
                @if($variants->count() > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Varianten-Typen</h3>
                        <div class="space-y-2">
                            @foreach($variants->groupBy('type') as $type => $group)
                                <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ \Platform\Brands\Models\BrandsLogoVariant::TYPES[$type] ?? $type }}</span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">{{ $group->count() }}</span>
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

    <livewire:brands.logo-board-settings-modal />
    <livewire:brands.logo-variant-modal />
</x-ui-page>
