<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$contentBoard->name" icon="heroicon-o-document-text">
            <x-slot name="actions">
                @if($contentBoard->multiContentBoardSlot && $contentBoard->multiContentBoardSlot->multiContentBoard)
                    <a href="{{ route('brands.multi-content-boards.show', $contentBoard->multiContentBoardSlot->multiContentBoard) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        <span>Zurück zum Multi-Content-Board</span>
                    </a>
                @else
                    <a href="{{ route('brands.brands.show', $contentBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        <span>Zurück zur Marke</span>
                    </a>
                @endif
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $contentBoard->name }}</h1>
                @if($contentBoard->description)
                    <p class="text-[var(--ui-secondary)] mb-3">{{ $contentBoard->description }}</p>
                @endif
                <div 
                    x-data="{ copied: false }"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg"
                >
                    <span class="text-xs font-mono text-[var(--ui-muted)]">{{ $contentBoard->uuid }}</span>
                    <button
                        type="button"
                        @click="
                            navigator.clipboard.writeText('{{ $contentBoard->uuid }}');
                            copied = true;
                            setTimeout(() => copied = false, 2000);
                        "
                        class="p-1 rounded hover:bg-white transition-colors"
                        title="UUID kopieren"
                    >
                        <span x-show="!copied">
                            @svg('heroicon-o-clipboard', 'w-3.5 h-3.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)]')
                        </span>
                        <span x-show="copied" x-cloak>
                            @svg('heroicon-o-check', 'w-3.5 h-3.5 text-green-600')
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Sections Section --}}
        <div>
            <div class="flex items-center justify-between mb-4">
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
                <div class="space-y-6">
                    @foreach($contentBoard->sections as $section)
                        {{-- Section (volle Breite) --}}
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                            <div class="p-4 border-b border-[var(--ui-border)]/40 flex items-center justify-between">
                                <div class="flex-1">
                                    @can('update', $contentBoard)
                                        <input 
                                            type="text"
                                            value="{{ $section->name }}"
                                            wire:blur="updateSectionName({{ $section->id }}, $event.target.value)"
                                            class="text-lg font-semibold text-[var(--ui-secondary)] bg-transparent border-none p-0 focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] rounded px-1 -ml-1 w-full"
                                        />
                                    @else
                                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $section->name }}</h3>
                                    @endcan
                                    @if($section->description)
                                        <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $section->description }}</p>
                                    @endif
                                    <div 
                                        x-data="{ copied: false }"
                                        class="inline-flex items-center gap-2 px-2 py-1 mt-2 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded text-xs"
                                    >
                                        <span class="font-mono text-[var(--ui-muted)]">{{ $section->uuid }}</span>
                                        <button
                                            type="button"
                                            @click="
                                                navigator.clipboard.writeText('{{ $section->uuid }}');
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                            class="p-0.5 rounded hover:bg-white transition-colors"
                                            title="UUID kopieren"
                                        >
                                            <span x-show="!copied">
                                                @svg('heroicon-o-clipboard', 'w-3 h-3 text-[var(--ui-muted)] hover:text-[var(--ui-primary)]')
                                            </span>
                                            <span x-show="copied" x-cloak>
                                                @svg('heroicon-o-check', 'w-3 h-3 text-green-600')
                                            </span>
                                        </button>
                                    </div>
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
                                            <div class="flex items-center gap-3 flex-1 flex-wrap">
                                                @can('update', $contentBoard)
                                                    <input 
                                                        type="text"
                                                        value="{{ $row->name }}"
                                                        wire:blur="updateRowName({{ $row->id }}, $event.target.value)"
                                                        class="text-sm font-semibold text-[var(--ui-secondary)] bg-transparent border-none p-0 focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] rounded px-1 -ml-1"
                                                    />
                                                @else
                                                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $row->name }}</h4>
                                                @endcan
                                                @if($row->description)
                                                    <span class="text-xs text-[var(--ui-muted)]">{{ $row->description }}</span>
                                                @endif
                                                @php
                                                    $totalSpan = $row->blocks->sum('span');
                                                @endphp
                                                <span class="text-xs px-2 py-0.5 rounded {{ $totalSpan > 12 ? 'bg-red-100 text-red-700' : ($totalSpan == 12 ? 'bg-green-100 text-green-700' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)]') }}">
                                                    Span: {{ $totalSpan }}/12
                                                </span>
                                                <div 
                                                    x-data="{ copied: false }"
                                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-white border border-[var(--ui-border)]/40 rounded text-xs"
                                                >
                                                    <span class="font-mono text-[var(--ui-muted)] text-[10px]">{{ $row->uuid }}</span>
                                                    <button
                                                        type="button"
                                                        @click="
                                                            navigator.clipboard.writeText('{{ $row->uuid }}');
                                                            copied = true;
                                                            setTimeout(() => copied = false, 2000);
                                                        "
                                                        class="p-0.5 rounded hover:bg-[var(--ui-muted-5)] transition-colors"
                                                        title="UUID kopieren"
                                                    >
                                                        <span x-show="!copied">
                                                            @svg('heroicon-o-clipboard', 'w-2.5 h-2.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)]')
                                                        </span>
                                                        <span x-show="copied" x-cloak>
                                                            @svg('heroicon-o-check', 'w-2.5 h-2.5 text-green-600')
                                                        </span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @can('update', $contentBoard)
                                                    @php
                                                        $totalSpan = $row->blocks->sum('span');
                                                    @endphp
                                                    <x-ui-button 
                                                        variant="primary" 
                                                        size="xs" 
                                                        wire:click="createBlock({{ $row->id }})"
                                                        :disabled="$totalSpan >= 12"
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
                                                            class="group bg-white rounded-lg border border-[var(--ui-border)]/40 hover:border-[var(--ui-primary)]/60 hover:shadow-sm transition-all relative cursor-pointer"
                                                            style="grid-column: span {{ $block->span }};"
                                                            @can('update', $contentBoard)
                                                                x-data
                                                                @click="$dispatch('open-modal-content-board-block-settings', { blockId: {{ $block->id }} })"
                                                            @endcan
                                                        >
                                                            {{-- Block Content --}}
                                                            <div class="p-4">
                                                                <div class="flex items-start justify-between gap-3">
                                                                    <div class="flex-1 min-w-0">
                                                                        <h5 class="text-sm font-medium text-[var(--ui-secondary)] leading-tight">
                                                                            {{ $block->name }}
                                                                        </h5>
                                                                        @if($block->description)
                                                                            <p class="text-xs text-[var(--ui-muted)] mt-1.5 leading-relaxed">
                                                                                {{ $block->description }}
                                                                            </p>
                                                                        @endif
                                                                        <div 
                                                                            x-data="{ copied: false }"
                                                                            class="inline-flex items-center gap-1.5 px-2 py-0.5 mt-2 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded text-xs"
                                                                        >
                                                                            <span class="font-mono text-[var(--ui-muted)] text-[10px]">{{ $block->uuid }}</span>
                                                                            <button
                                                                                type="button"
                                                                                @click.stop="
                                                                                    navigator.clipboard.writeText('{{ $block->uuid }}');
                                                                                    copied = true;
                                                                                    setTimeout(() => copied = false, 2000);
                                                                                "
                                                                                class="p-0.5 rounded hover:bg-white transition-colors"
                                                                                title="UUID kopieren"
                                                                            >
                                                                                <span x-show="!copied">
                                                                                    @svg('heroicon-o-clipboard', 'w-2.5 h-2.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)]')
                                                                                </span>
                                                                                <span x-show="copied" x-cloak>
                                                                                    @svg('heroicon-o-check', 'w-2.5 h-2.5 text-green-600')
                                                                                </span>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    @can('update', $contentBoard)
                                                                        <button 
                                                                            type="button"
                                                                            @click.stop="$dispatch('open-modal-content-board-block-settings', { blockId: {{ $block->id }} })"
                                                                            class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 p-1.5 rounded-md hover:bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]"
                                                                            title="Block-Einstellungen"
                                                                        >
                                                                            @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                                                        </button>
                                                                    @endcan
                                                                </div>
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
                </div>
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
                        @if($contentBoard->multiContentBoardSlot && $contentBoard->multiContentBoardSlot->multiContentBoard)
                            <a href="{{ route('brands.multi-content-boards.show', $contentBoard->multiContentBoardSlot->multiContentBoard) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                                @svg('heroicon-o-arrow-left', 'w-4 h-4')
                                <span>Zurück zum Multi-Content-Board</span>
                            </a>
                        @endif
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
    <livewire:brands.content-board-block-settings-modal/>
</x-ui-page>
