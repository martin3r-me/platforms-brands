<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$title ?: $card->title" icon="heroicon-o-document-text">
            <div class="mt-1 flex items-center gap-2 text-sm text-[color:var(--nx-faint)]">
                <a href="{{ route('brands.brands.show', $card->kanbanBoard->brand) }}" class="flex items-center gap-1 text-[color:var(--nx-text)] hover:text-[color:var(--nx-accent)]">
                    @svg('heroicon-o-tag', 'w-4 h-4')
                    {{ $card->kanbanBoard->brand->name }}
                </a>
                <span>›</span>
                <a href="{{ route('brands.kanban-boards.show', $card->kanbanBoard) }}" class="flex items-center gap-1 text-[color:var(--nx-text)] hover:text-[color:var(--nx-accent)]">
                    @svg('heroicon-o-view-columns', 'w-4 h-4')
                    {{ $card->kanbanBoard->name }}
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
                <x-nx-button variant="ghost" size="sm" :href="route('brands.kanban-boards.show', $card->kanbanBoard)">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4') Zurück zum Board
                </x-nx-button>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container class="max-w-4xl mx-auto">
        @can('update', $card)
            <div
                x-data="{
                    savedLabel: '—',
                    isSaving: false,
                    init() {
                        if (window.Livewire) {
                            Livewire.on('brands-kanban-saved', (payload) => {
                                if (!payload || payload.cardId !== {{ (int) $card->id }}) return;
                                this.savedLabel = 'Gespeichert';
                                this.isSaving = false;
                            });
                        }
                    },
                    saveNow() {
                        this.isSaving = true;
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
                        </div>
                        <x-nx-button type="button" variant="secondary" size="sm" @click="saveNow()">Speichern</x-nx-button>
                    </div>
                </div>

                {{-- Description --}}
                <div class="mb-6">
                    <x-nx-input-textarea
                        name="description"
                        label="Beschreibung"
                        wire:model.defer="description"
                        placeholder="Beschreibung für diese Card..."
                        :errorKey="'description'"
                    />
                </div>

                <x-core-extra-fields-section
                    :definitions="$this->extraFieldDefinitions"
                    :model="$card"
                    class="mt-8"
                />
            </div>
        @else
            {{-- Read-only View --}}
            <div class="space-y-6">
                <div>
                    <h1 class="mb-4 text-3xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $card->title }}</h1>

                    @if($card->description)
                        <x-nx-card>
                            <p class="text-sm text-[color:var(--nx-muted)]">{{ $card->description }}</p>
                        </x-nx-card>
                    @else
                        <x-nx-empty icon="heroicon-o-document-text">Noch keine Beschreibung</x-nx-empty>
                    @endif
                </div>
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
