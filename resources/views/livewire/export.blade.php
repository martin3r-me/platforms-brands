<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Export" icon="heroicon-o-arrow-down-tray">
            <x-slot name="breadcrumbs">
                <a href="{{ route('brands.brands.show', $brand) }}" class="text-[var(--ui-muted)] hover:text-[var(--ui-primary)] transition-colors">
                    {{ $brand->name }}
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Komplette Marke exportieren --}}
        <div>
            <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50">
                    @svg('heroicon-o-building-office', 'w-5 h-5 text-emerald-600')
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Komplette Marke exportieren</h2>
                    <p class="text-sm text-[var(--ui-muted)]">Alle Boards, Einträge, Einstellungen und Medien-Referenzen</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $brand->name }}</h3>
                        @if($brand->description)
                            <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $brand->description }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-3">
                            <span class="text-xs text-[var(--ui-muted)] bg-[var(--ui-muted-5)] px-2.5 py-1 rounded-md">
                                {{ $boards->count() }} {{ $boards->count() === 1 ? 'Board' : 'Boards' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        @foreach($formats as $fmt)
                            <a
                                href="{{ route('brands.export.download-brand', ['brandsBrand' => $brand->id, 'format' => $fmt['key']]) }}"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                                    {{ $fmt['key'] === 'json'
                                        ? 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200'
                                        : 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200' }}"
                            >
                                @if($fmt['key'] === 'json')
                                    @svg('heroicon-o-code-bracket', 'w-4 h-4')
                                @else
                                    @svg('heroicon-o-document', 'w-4 h-4')
                                @endif
                                {{ $fmt['label'] }}
                                @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Einzelne Boards exportieren --}}
        <div>
            <div class="flex items-center gap-3 mb-6">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-violet-50 to-purple-50">
                    @svg('heroicon-o-squares-2x2', 'w-5 h-5 text-violet-600')
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Einzelne Boards exportieren</h2>
                    <p class="text-sm text-[var(--ui-muted)]">Wähle ein Board und ein Format für den Export</p>
                </div>
            </div>

            @if($boards->count() > 0)
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                    <div class="divide-y divide-[var(--ui-border)]/40">
                        @foreach($boards as $board)
                            <div class="p-4 hover:bg-[var(--ui-muted-5)] transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg
                                            @switch($board['type'])
                                                @case('ci') bg-amber-50 @break
                                                @case('content') bg-blue-50 @break
                                                @case('social') bg-purple-50 @break
                                                @case('kanban') bg-indigo-50 @break
                                                @case('multi_content') bg-green-50 @break
                                            @endswitch
                                        ">
                                            @switch($board['type'])
                                                @case('ci')
                                                    @svg('heroicon-o-paint-brush', 'w-5 h-5 text-amber-600')
                                                    @break
                                                @case('content')
                                                    @svg('heroicon-o-document-text', 'w-5 h-5 text-blue-600')
                                                    @break
                                                @case('social')
                                                    @svg('heroicon-o-share', 'w-5 h-5 text-purple-600')
                                                    @break
                                                @case('kanban')
                                                    @svg('heroicon-o-view-columns', 'w-5 h-5 text-indigo-600')
                                                    @break
                                                @case('multi_content')
                                                    @svg('heroicon-o-squares-2x2', 'w-5 h-5 text-green-600')
                                                    @break
                                            @endswitch
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-semibold text-[var(--ui-secondary)] truncate">{{ $board['name'] }}</h4>
                                            <span class="text-xs text-[var(--ui-muted)]">{{ $board['type_label'] }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 ml-3 flex-shrink-0">
                                        @foreach($formats as $fmt)
                                            <a
                                                href="{{ route('brands.export.download-board', ['boardType' => $board['route_type'], 'boardId' => $board['id'], 'format' => $fmt['key']]) }}"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200
                                                    {{ $fmt['key'] === 'json'
                                                        ? 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200'
                                                        : 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200' }}"
                                                title="{{ $board['name'] }} als {{ $fmt['label'] }} exportieren"
                                            >
                                                @if($fmt['key'] === 'json')
                                                    @svg('heroicon-o-code-bracket', 'w-3.5 h-3.5')
                                                @else
                                                    @svg('heroicon-o-document', 'w-3.5 h-3.5')
                                                @endif
                                                {{ $fmt['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                        @svg('heroicon-o-squares-2x2', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Keine Boards vorhanden</h3>
                    <p class="text-sm text-[var(--ui-muted)]">Erstelle zuerst Boards in der Marken-Ansicht, um diese einzeln exportieren zu können.</p>
                </div>
            @endif
        </div>

        {{-- Export-Info --}}
        <div class="bg-[var(--ui-muted-5)] rounded-xl border border-[var(--ui-border)]/40 p-6">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    @svg('heroicon-o-information-circle', 'w-5 h-5 text-[var(--ui-muted)]')
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)] mb-2">Hinweise zum Export</h4>
                    <ul class="text-sm text-[var(--ui-muted)] space-y-1.5">
                        <li class="flex items-start gap-2">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-blue-50 text-blue-600 text-xs font-bold flex-shrink-0 mt-0.5">J</span>
                            <span><strong>JSON</strong> – Maschinenlesbares Format mit allen Daten, Feldern und Medien-Referenzen. Geeignet für Backups, Datenübertragung und Weiterverarbeitung.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-red-50 text-red-600 text-xs font-bold flex-shrink-0 mt-0.5">P</span>
                            <span><strong>PDF</strong> – Formatiertes, druckfertiges Dokument im Brand Book-Stil. Berücksichtigt Markenfarben und ist nach Boards strukturiert.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
