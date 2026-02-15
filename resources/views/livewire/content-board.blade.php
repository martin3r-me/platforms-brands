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

        {{-- Blöcke --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Blöcke</h2>
                @can('update', $contentBoard)
                    <x-ui-button variant="primary" size="sm" wire:click="createBlock">
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Block erstellen</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>

            @if($contentBoard->blocks->count() > 0)
                <div class="space-y-3">
                    @foreach($contentBoard->blocks as $block)
                        @php
                            $hasContent = $block->content_type && $block->content;
                        @endphp
                        <div
                            class="group bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:border-[var(--ui-primary)]/60 hover:shadow-md transition-all relative {{ $hasContent ? 'cursor-pointer' : '' }}"
                            @if($hasContent)
                                @click="window.location.href = '{{ route('brands.content-board-blocks.show', ['brandsContentBoardBlock' => $block->id, 'type' => $block->content_type]) }}'"
                            @elseif(auth()->user()->can('update', $contentBoard))
                                x-data
                                @click="$dispatch('open-modal-content-board-block-settings', { blockId: {{ $block->id }} })"
                            @endif
                        >
                            <div class="p-4 lg:p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        @can('update', $contentBoard)
                                            <input
                                                type="text"
                                                value="{{ $block->name }}"
                                                wire:blur="updateBlockName({{ $block->id }}, $event.target.value)"
                                                @click.stop
                                                class="text-sm font-medium text-[var(--ui-secondary)] bg-transparent border-none p-0 focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] rounded px-1 -ml-1 w-full"
                                            />
                                        @else
                                            <h5 class="text-sm font-medium text-[var(--ui-secondary)] leading-tight">
                                                {{ $block->name }}
                                            </h5>
                                        @endcan
                                        @if($block->description)
                                            <p class="text-xs text-[var(--ui-muted)] mt-1.5 leading-relaxed">
                                                {{ $block->description }}
                                            </p>
                                        @endif

                                        {{-- Content-Ausgabe je nach Typ --}}
                                        @if($hasContent)
                                            @if($block->content_type === 'text' && $block->content)
                                                <div class="mt-3 text-sm text-[var(--ui-secondary)] markdown-content-preview">
                                                    <div class="line-clamp-3">
                                                        {!! \Illuminate\Support\Str::markdown($block->content->content ?? '') !!}
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <p class="text-xs text-[var(--ui-muted)] italic mt-2">Noch kein Content</p>
                                        @endif

                                        <div class="flex items-center gap-3 mt-2">
                                            @if($block->content_type)
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-[var(--ui-primary-10)] text-[var(--ui-primary)] border border-[var(--ui-primary)]/20">
                                                    {{ ucfirst($block->content_type) }}
                                                </span>
                                            @endif
                                            <div
                                                x-data="{ copied: false }"
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded text-xs"
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

                            {{-- Edit Icon unten rechts, nur wenn Content vorhanden --}}
                            @if($hasContent)
                                <a
                                    href="{{ route('brands.content-board-blocks.show', ['brandsContentBoardBlock' => $block->id, 'type' => $block->content_type]) }}"
                                    @click.stop
                                    class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center w-8 h-8 rounded-md bg-[var(--ui-primary)] text-white hover:bg-[var(--ui-primary)]/90 shadow-sm"
                                    title="Block bearbeiten"
                                >
                                    @svg('heroicon-o-pencil', 'w-4 h-4')
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                        @svg('heroicon-o-document-text', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Blöcke</h3>
                    <p class="text-sm text-[var(--ui-muted)] mb-4">Erstelle deinen ersten Block für dieses Content Board.</p>
                    @can('update', $contentBoard)
                        <x-ui-button variant="primary" size="sm" wire:click="createBlock">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Block erstellen</span>
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

@push('styles')
<style>
    /* Markdown Content Preview Styling für Content Board Blocks */
    .markdown-content-preview {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        line-height: 1.6;
        color: var(--ui-secondary);
    }
    
    .markdown-content-preview h1,
    .markdown-content-preview h2,
    .markdown-content-preview h3,
    .markdown-content-preview h4 {
        font-weight: 600;
        margin-top: 0.5em;
        margin-bottom: 0.25em;
        line-height: 1.3;
    }
    
    .markdown-content-preview h1 {
        font-size: 1.5em;
    }
    
    .markdown-content-preview h2 {
        font-size: 1.25em;
    }
    
    .markdown-content-preview h3 {
        font-size: 1.1em;
    }
    
    .markdown-content-preview h4 {
        font-size: 1em;
    }
    
    .markdown-content-preview p {
        margin-bottom: 0.5em;
    }
    
    .markdown-content-preview ul,
    .markdown-content-preview ol {
        margin-bottom: 0.5em;
        padding-left: 1.25em;
    }
    
    .markdown-content-preview li {
        margin-bottom: 0.25em;
    }
    
    .markdown-content-preview code {
        background: var(--ui-muted-5);
        padding: 0.15em 0.3em;
        border-radius: 3px;
        font-size: 0.9em;
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    }
    
    .markdown-content-preview pre {
        background: var(--ui-muted-5);
        padding: 0.75em;
        border-radius: 6px;
        overflow-x: auto;
        margin-bottom: 0.5em;
    }
    
    .markdown-content-preview pre code {
        background: transparent;
        padding: 0;
    }
    
    .markdown-content-preview blockquote {
        border-left: 2px solid var(--ui-primary);
        padding-left: 0.75em;
        margin-left: 0;
        color: var(--ui-muted);
        font-style: italic;
    }
    
    .markdown-content-preview strong {
        font-weight: 600;
    }
    
    .markdown-content-preview em {
        font-style: italic;
    }
    
    .markdown-content-preview a {
        color: var(--ui-primary);
        text-decoration: underline;
    }
    
    .markdown-content-preview a:hover {
        color: var(--ui-primary);
        text-decoration: none;
    }
    
    /* Line clamp für Preview */
    .markdown-content-preview .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush
