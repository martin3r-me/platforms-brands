<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$contentBoard->name" icon="heroicon-o-document-text">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $contentBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
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
                <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $contentBoard->name }}</h1>
                @if($contentBoard->description)
                    <p class="text-[var(--ui-secondary)]">{{ $contentBoard->description }}</p>
                @endif
            </div>
        </div>

        {{-- Sections Section --}}
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Sections</h2>
                @can('update', $contentBoard)
                    <x-ui-button variant="primary" size="sm" wire:click="createSection">
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Section erstellen</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>

            @if($contentBoard->sections->count() > 0)
                @foreach($contentBoard->sections as $section)
                    {{-- Section (volle Breite) --}}
                    <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-[var(--ui-border)]/40 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $section->name }}</h3>
                                @if($section->description)
                                    <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $section->description }}</p>
                                @endif
                            </div>
                            @can('update', $contentBoard)
                                <x-ui-button 
                                    variant="danger-outline" 
                                    size="xs" 
                                    wire:click="deleteSection({{ $section->id }})"
                                    wire:confirm="Möchtest du diese Section wirklich löschen? Alle Rows und Blocks werden ebenfalls gelöscht."
                                >
                                    <span class="inline-flex items-center gap-1">
                                        @svg('heroicon-o-trash', 'w-3 h-3')
                                        <span>Löschen</span>
                                    </span>
                                </x-ui-button>
                            @endcan
                        </div>
                        
                        {{-- Rows innerhalb der Section --}}
                        <div class="p-4 space-y-4">
                            @foreach($section->rows as $row)
                                <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 overflow-hidden">
                                    <div class="p-3 border-b border-[var(--ui-border)]/40 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <h4 class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $row->name }}</h4>
                                            @if($row->description)
                                                <span class="text-xs text-[var(--ui-muted)]">{{ $row->description }}</span>
                                            @endif
                                            @php
                                                $totalSpan = $row->blocks->sum('span');
                                            @endphp
                                            <span class="text-xs px-2 py-0.5 rounded {{ $totalSpan > 12 ? 'bg-red-100 text-red-700' : ($totalSpan == 12 ? 'bg-green-100 text-green-700' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)]') }}">
                                                Span: {{ $totalSpan }}/12
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @can('update', $contentBoard)
                                                <x-ui-button 
                                                    variant="primary" 
                                                    size="xs" 
                                                    wire:click="createBlock({{ $row->id }})"
                                                    :disabled="$row->blocks->count() >= 12"
                                                >
                                                    <span class="inline-flex items-center gap-1">
                                                        @svg('heroicon-o-plus', 'w-3 h-3')
                                                        <span>Block</span>
                                                    </span>
                                                </x-ui-button>
                                                <x-ui-button 
                                                    variant="danger-outline" 
                                                    size="xs" 
                                                    wire:click="deleteRow({{ $row->id }})"
                                                    wire:confirm="Möchtest du diese Row wirklich löschen? Alle Blocks werden ebenfalls gelöscht."
                                                >
                                                    <span class="inline-flex items-center gap-1">
                                                        @svg('heroicon-o-trash', 'w-3 h-3')
                                                    </span>
                                                </x-ui-button>
                                            @endcan
                                        </div>
                                    </div>
                                    
                                    <div class="p-3">
                                        @if($row->blocks->count() > 0)
                                            <div class="grid grid-cols-12 gap-2">
                                                @foreach($row->blocks as $block)
                                                    <div 
                                                        class="bg-white rounded-lg border border-[var(--ui-border)]/40 p-3 hover:border-[var(--ui-primary)]/40 transition-colors relative group"
                                                        style="grid-column: span {{ $block->span }};"
                                                    >
                                                        <div class="flex items-start justify-between mb-2">
                                                            <div class="flex-1">
                                                                <h5 class="text-xs font-semibold text-[var(--ui-secondary)]">{{ $block->name }}</h5>
                                                                @if($block->description)
                                                                    <p class="text-xs text-[var(--ui-muted)] mt-1">{{ $block->description }}</p>
                                                                @endif
                                                            </div>
                                                            @can('update', $contentBoard)
                                                                <div class="flex items-center gap-1">
                                                                    <input 
                                                                        type="number" 
                                                                        min="1" 
                                                                        max="12" 
                                                                        value="{{ $block->span }}"
                                                                        wire:change="updateBlockSpan({{ $block->id }}, $event.target.value)"
                                                                        class="w-12 text-xs text-center border border-[var(--ui-border)] rounded px-1 py-0.5 focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"
                                                                    />
                                                                    <x-ui-button 
                                                                        variant="danger-outline" 
                                                                        size="xs" 
                                                                        wire:click="deleteBlock({{ $block->id }})"
                                                                        wire:confirm="Möchtest du diesen Block wirklich löschen?"
                                                                        class="opacity-0 group-hover:opacity-100 transition-opacity"
                                                                    >
                                                                        <span class="inline-flex items-center">
                                                                            @svg('heroicon-o-trash', 'w-3 h-3')
                                                                        </span>
                                                                    </x-ui-button>
                                                                </div>
                                                            @else
                                                                <span class="text-xs text-[var(--ui-muted)] bg-[var(--ui-muted-5)] px-1.5 py-0.5 rounded">
                                                                    {{ $block->span }}
                                                                </span>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-4 border-2 border-dashed border-[var(--ui-border)]/40 rounded-lg">
                                                <p class="text-xs text-[var(--ui-muted)] mb-2">Noch keine Blöcke</p>
                                                @can('update', $contentBoard)
                                                    <x-ui-button 
                                                        variant="primary" 
                                                        size="xs" 
                                                        wire:click="createBlock({{ $row->id }})"
                                                    >
                                                        <span class="inline-flex items-center gap-1">
                                                            @svg('heroicon-o-plus', 'w-3 h-3')
                                                            <span>Block hinzufügen</span>
                                                        </span>
                                                    </x-ui-button>
                                                @endcan
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            {{-- Neue Row hinzufügen --}}
                            @can('update', $contentBoard)
                                <div class="border-2 border-dashed border-[var(--ui-border)]/40 rounded-lg p-3 text-center">
                                    <x-ui-button variant="secondary-outline" size="sm" wire:click="createRow({{ $section->id }})">
                                        <span class="inline-flex items-center gap-2">
                                            @svg('heroicon-o-plus', 'w-4 h-4')
                                            <span>Row hinzufügen</span>
                                        </span>
                                    </x-ui-button>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endforeach
            @else
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                        @svg('heroicon-o-squares-2x2', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Sections</h3>
                    <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle deine erste Section für dieses Content Board.</p>
                    @can('update', $contentBoard)
                        <x-ui-button variant="primary" size="sm" wire:click="createSection">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Section erstellen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $contentBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurück zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $contentBoard)
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-content-board-settings', { contentBoardId: {{ $contentBoard->id }} })" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
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
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-[var(--ui-primary-10)] text-[var(--ui-primary)] border border-[var(--ui-primary)]/20">
                                Content Board
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $contentBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Letzte Aktivitäten</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)] hover:bg-[var(--ui-muted)] transition-colors">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-[var(--ui-secondary)] leading-snug">
                                        {{ $activity['title'] ?? 'Aktivität' }}
                                    </div>
                                </div>
                                @if(($activity['type'] ?? null) === 'system')
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 text-xs text-[var(--ui-muted)]">
                                            @svg('heroicon-o-cog', 'w-3 h-3')
                                            System
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-[var(--ui-muted)]">
                                @svg('heroicon-o-clock', 'w-3 h-3')
                                <span>{{ $activity['time'] ?? '' }}</span>
                            </div>
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

    <livewire:brands.content-board-settings-modal/>
</x-ui-page>
