<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$title ?: $card->title" icon="heroicon-o-document-text">
            <div class="mt-1 flex items-center gap-2 text-sm text-[color:var(--nx-faint)]">
                <a href="{{ route('brands.brands.show', $card->socialBoard->brand) }}" class="flex items-center gap-1 text-[color:var(--nx-text)] hover:text-[color:var(--nx-accent)]">
                    @svg('heroicon-o-tag', 'w-4 h-4')
                    {{ $card->socialBoard->brand->name }}
                </a>
                <span>›</span>
                <a href="{{ route('brands.social-boards.show', $card->socialBoard) }}" class="flex items-center gap-1 text-[color:var(--nx-text)] hover:text-[color:var(--nx-accent)]">
                    @svg('heroicon-o-share', 'w-4 h-4')
                    {{ $card->socialBoard->name }}
                </a>
                @if($card->slot)
                    <span>›</span>
                    <span class="flex items-center gap-1">
                        @svg('heroicon-o-view-columns', 'w-4 h-4')
                        {{ $card->slot->name }}
                    </span>
                @endif
            </div>
            <x-slot name="actions">
                <x-nx-button variant="ghost" size="sm" :href="route('brands.social-boards.show', $card->socialBoard)">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4') Zurück zum Board
                </x-nx-button>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container class="max-w-4xl mx-auto">
        @can('update', $card)
            {{-- Bear/Obsidian-like Editor --}}
            <div
                x-data="{
                    editor: null,
                    isSaving: false,
                    savedLabel: '—',
                    debounceTimer: null,
                    boot() {
                        const Editor = window.ToastUIEditor;
                        if (!Editor) return false;

                        if (this.editor && typeof this.editor.destroy === 'function') {
                            this.editor.destroy();
                        }

                        this.editor = new Editor({
                            el: this.$refs.editorEl,
                            height: '70vh',
                            initialEditType: 'wysiwyg',
                            previewStyle: 'tab',
                            hideModeSwitch: true,
                            usageStatistics: false,
                            placeholder: 'Schreibe deine Social Media Caption…  😀  / Überschriften, Listen, Links, Code',
                            toolbarItems: [
                                ['heading', 'bold', 'italic', 'strike'],
                                ['ul', 'ol', 'task', 'quote'],
                                ['link', 'code', 'codeblock', 'hr'],
                            ],
                            initialValue: @js($bodyMd ?? ''),
                        });

                        // Sync Editor -> Livewire state (debounced, ohne DB-write)
                        this.editor.on('change', () => {
                            clearTimeout(this.debounceTimer);
                            this.debounceTimer = setTimeout(() => {
                                const md = this.editor.getMarkdown();
                                $wire.set('bodyMd', md, false);
                            }, 500);
                        });

                        // Livewire events (wire:ignore)
                        const bindLivewire = () => {
                            if (!window.Livewire) return;
                            Livewire.on('brands-sync-editor', (payload) => {
                                if (!payload || payload.cardId !== {{ (int) $card->id }}) return;
                                if (typeof payload.title === 'string') {
                                    $wire.set('title', payload.title, false);
                                }
                                if (typeof payload.bodyMd === 'string' && this.editor) {
                                    this.editor.setMarkdown(payload.bodyMd);
                                }
                                this.savedLabel = '—';
                            });

                            Livewire.on('brands-saved', (payload) => {
                                if (!payload || payload.cardId !== {{ (int) $card->id }}) return;
                                this.savedLabel = 'Gespeichert';
                                this.isSaving = false;
                            });
                        };

                        if (window.Livewire) {
                            bindLivewire();
                        } else {
                            document.addEventListener('livewire:init', bindLivewire, { once: true });
                        }

                        return true;
                    },
                    init() {
                        if (!this.boot()) {
                            window.addEventListener('toastui:ready', () => this.boot(), { once: true });
                        }
                    },
                    saveNow() {
                        if (!this.editor) return;
                        this.isSaving = true;
                        const md = this.editor.getMarkdown();
                        $wire.set('bodyMd', md, false);
                        $wire.save();
                    },
                }"
                class="min-h-[calc(100vh-220px)]"
            >
                {{-- Title + tiny status --}}
                <div class="mb-6 flex items-start justify-between gap-4">
                    <input
                        type="text"
                        wire:model.live="title"
                        placeholder="Titel…"
                        class="w-full border-0 bg-transparent text-3xl font-semibold tracking-tight text-[color:var(--nx-text)] placeholder:text-[color:var(--nx-faint)] focus:outline-none focus:ring-0"
                    />

                    <div class="flex flex-shrink-0 items-center gap-3 pt-2">
                        <div class="text-xs text-[color:var(--nx-faint)]">
                            <span x-text="savedLabel"></span>
                            <span class="mx-1">·</span>
                            <span>⌘S</span>
                        </div>
                        <x-nx-button type="button" variant="secondary" size="sm" @click="saveNow()">Speichern</x-nx-button>
                    </div>
                </div>

                {{-- Description (interner Kommentar) --}}
                <div class="mb-6">
                    <x-nx-input-textarea
                        name="description"
                        label="Interner Kommentar (optional)"
                        wire:model.defer="description"
                        placeholder="Interner Kommentar für diese Card..."
                        :errorKey="'description'"
                    />
                </div>

                <div class="social-card-editor-shell">
                    <div wire:ignore x-ref="editorEl"></div>
                </div>
            </div>
        @else
            {{-- Read-only View --}}
            <div class="space-y-6">
                <div>
                    <h1 class="mb-4 text-3xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $card->title }}</h1>

                    @if($card->description)
                        <x-nx-card>
                            <p class="text-sm italic text-[color:var(--nx-muted)]">{{ $card->description }}</p>
                        </x-nx-card>
                    @endif
                </div>

                @if($card->body_md)
                    <div class="markdown-content">
                        {!! \Illuminate\Support\Str::markdown($card->body_md) !!}
                    </div>
                @else
                    <x-nx-empty icon="heroicon-o-document-text">Noch kein Inhalt</x-nx-empty>
                @endif
            </div>
        @endcan
    </x-ui-page-container>

    <x-slot name="sidebar">
        @include('brands::partials.board-sidebar', ['sidebarTitle' => 'Card-Übersicht', 'detailRows' => array_filter([
            $card->slot ? ['label' => 'Slot', 'value' => $card->slot->name] : null,
            ['label' => 'Erstellt', 'value' => $card->created_at->format('d.m.Y')],
        ])])
    </x-slot>

</x-ui-page>

@push('styles')
<style>
    /* Toast UI Editor: make it feel like Bear/Obsidian (clean, minimal) */
    .social-card-editor-shell .toastui-editor-defaultUI {
        border: 1px solid var(--nx-line);
        border-radius: 12px;
        overflow: hidden;
    }
    .social-card-editor-shell .toastui-editor-toolbar {
        background: color-mix(in srgb, var(--nx-hover) 70%, transparent);
        border-bottom: 1px solid var(--nx-line);
    }
    .social-card-editor-shell .toastui-editor-contents {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        font-size: 17px;
        line-height: 1.7;
    }
    .social-card-editor-shell .toastui-editor-defaultUI-toolbar button {
        border-radius: 8px;
    }
    .social-card-editor-shell .toastui-editor-mode-switch {
        display: none !important;
    }

    /* Obsidian/Bear Style Markdown Rendering */
    .markdown-content {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        font-size: 17px;
        line-height: 1.7;
        color: var(--nx-text);
    }

    .markdown-content h1 {
        font-size: 2.5em;
        font-weight: 700;
        margin-top: 1.5em;
        margin-bottom: 0.5em;
        line-height: 1.2;
    }

    .markdown-content h2 {
        font-size: 2em;
        font-weight: 600;
        margin-top: 1.3em;
        margin-bottom: 0.5em;
        line-height: 1.3;
    }

    .markdown-content h3 {
        font-size: 1.5em;
        font-weight: 600;
        margin-top: 1.2em;
        margin-bottom: 0.5em;
    }

    .markdown-content h4 {
        font-size: 1.25em;
        font-weight: 600;
        margin-top: 1em;
        margin-bottom: 0.5em;
    }

    .markdown-content p {
        margin-bottom: 1em;
    }
</style>
@endpush
