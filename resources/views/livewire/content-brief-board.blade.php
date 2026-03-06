<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$contentBriefBoard->name" icon="heroicon-o-document-magnifying-glass" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $contentBriefBoard->brand->name, 'href' => route('brands.brands.show', $contentBriefBoard->brand)],
            ['label' => $contentBriefBoard->name],
        ]">
            @can('update', $contentBriefBoard)
                <x-ui-button variant="ghost" size="sm" wire:click="startEditing">
                    @svg('heroicon-o-pencil', 'w-4 h-4')
                    <span>Bearbeiten</span>
                </x-ui-button>
            @endcan
            @can('delete', $contentBriefBoard)
                <x-ui-button variant="ghost" size="sm" wire:click="deleteBoard" wire:confirm="Content Brief wirklich löschen?" class="text-red-600 hover:text-red-700">
                    @svg('heroicon-o-trash', 'w-4 h-4')
                    <span>Löschen</span>
                </x-ui-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Brief-Übersicht" width="w-80">
            <div class="p-4 space-y-6">
                {{-- Status --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Status</h3>
                    <div class="flex flex-wrap gap-1">
                        @foreach($statuses as $key => $label)
                            <button
                                wire:click="updateStatus('{{ $key }}')"
                                class="px-2.5 py-1 text-xs font-medium rounded-full transition-colors
                                    {{ $contentBriefBoard->status === $key
                                        ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-300'
                                        : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:bg-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-xs text-[var(--ui-muted)]">Content-Typ</span>
                            <span class="text-xs font-medium text-[var(--ui-secondary)]">{{ $contentTypes[$contentBriefBoard->content_type] ?? $contentBriefBoard->content_type }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-xs text-[var(--ui-muted)]">Search Intent</span>
                            <span class="text-xs font-medium text-[var(--ui-secondary)]">{{ $searchIntents[$contentBriefBoard->search_intent] ?? $contentBriefBoard->search_intent }}</span>
                        </div>
                        @if($contentBriefBoard->target_slug)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] rounded-lg">
                                <span class="text-xs text-[var(--ui-muted)]">Ziel-URL</span>
                                <span class="text-xs font-medium text-[var(--ui-secondary)] truncate ml-2">{{ $contentBriefBoard->target_slug }}</span>
                            </div>
                        @endif
                        @if($contentBriefBoard->target_word_count)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] rounded-lg">
                                <span class="text-xs text-[var(--ui-muted)]">Ziel-Wortanzahl</span>
                                <span class="text-xs font-medium text-[var(--ui-secondary)]">{{ number_format($contentBriefBoard->target_word_count, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($contentBriefBoard->seoBoard)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] rounded-lg">
                                <span class="text-xs text-[var(--ui-muted)]">SEO Board</span>
                                <a href="{{ route('brands.seo-boards.show', $contentBriefBoard->seoBoard) }}" class="text-xs font-medium text-[var(--ui-primary)] hover:underline truncate ml-2">
                                    {{ $contentBriefBoard->seoBoard->name }}
                                </a>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] rounded-lg">
                            <span class="text-xs text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-xs font-medium text-[var(--ui-secondary)]">{{ $contentBriefBoard->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Edit Form (Overlay) --}}
        @if($editing)
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-6">
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Content Brief bearbeiten</h3>
                <form wire:submit="saveEditing" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Name / H1-Kandidat</label>
                        <input type="text" wire:model="editName" class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg text-sm focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]" />
                        @error('editName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Beschreibung</label>
                        <textarea wire:model="editDescription" rows="3" class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg text-sm focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Content-Typ</label>
                            <select wire:model="editContentType" class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg text-sm">
                                @foreach($contentTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Search Intent</label>
                            <select wire:model="editSearchIntent" class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg text-sm">
                                @foreach($searchIntents as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Status</label>
                            <select wire:model="editStatus" class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg text-sm">
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Ziel-Wortanzahl</label>
                            <input type="number" wire:model="editTargetWordCount" min="0" class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Ziel-URL / Slug</label>
                        <input type="text" wire:model="editTargetSlug" class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg text-sm" placeholder="/blog/mein-artikel" />
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <x-ui-button type="submit" variant="primary" size="sm">Speichern</x-ui-button>
                        <x-ui-button type="button" variant="secondary-outline" size="sm" wire:click="cancelEditing">Abbrechen</x-ui-button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Brief Card --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-[var(--ui-secondary)]">{{ $contentBriefBoard->name }}</h2>
                        @if($contentBriefBoard->description)
                            <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $contentBriefBoard->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @php
                            $statusColors = [
                                'draft' => 'bg-gray-100 text-gray-700',
                                'briefed' => 'bg-blue-100 text-blue-700',
                                'in_production' => 'bg-yellow-100 text-yellow-700',
                                'review' => 'bg-purple-100 text-purple-700',
                                'published' => 'bg-green-100 text-green-700',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColors[$contentBriefBoard->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statuses[$contentBriefBoard->status] ?? $contentBriefBoard->status }}
                        </span>
                    </div>
                </div>

                {{-- Metadata Grid --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                    <div class="bg-[var(--ui-muted-5)] rounded-lg p-3 text-center">
                        <div class="text-xs text-[var(--ui-muted)] mb-1">Content-Typ</div>
                        <div class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $contentTypes[$contentBriefBoard->content_type] ?? $contentBriefBoard->content_type }}</div>
                    </div>
                    <div class="bg-[var(--ui-muted-5)] rounded-lg p-3 text-center">
                        <div class="text-xs text-[var(--ui-muted)] mb-1">Search Intent</div>
                        <div class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $searchIntents[$contentBriefBoard->search_intent] ?? $contentBriefBoard->search_intent }}</div>
                    </div>
                    @if($contentBriefBoard->target_word_count)
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3 text-center">
                            <div class="text-xs text-[var(--ui-muted)] mb-1">Ziel-Wortanzahl</div>
                            <div class="text-sm font-semibold text-[var(--ui-secondary)]">{{ number_format($contentBriefBoard->target_word_count, 0, ',', '.') }}</div>
                        </div>
                    @endif
                    @if($contentBriefBoard->target_slug)
                        <div class="bg-[var(--ui-muted-5)] rounded-lg p-3 text-center">
                            <div class="text-xs text-[var(--ui-muted)] mb-1">Ziel-URL</div>
                            <div class="text-sm font-semibold text-[var(--ui-secondary)] truncate">{{ $contentBriefBoard->target_slug }}</div>
                        </div>
                    @endif
                </div>

                {{-- SEO Board Link --}}
                @if($contentBriefBoard->seoBoard)
                    <div class="mt-6 p-4 bg-lime-50 rounded-lg border border-lime-200">
                        <div class="flex items-center gap-3">
                            @svg('heroicon-o-magnifying-glass', 'w-5 h-5 text-lime-600')
                            <div>
                                <div class="text-xs text-lime-600 font-medium">Verknüpftes SEO Board</div>
                                <a href="{{ route('brands.seo-boards.show', $contentBriefBoard->seoBoard) }}" class="text-sm font-semibold text-lime-700 hover:underline">
                                    {{ $contentBriefBoard->seoBoard->name }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
