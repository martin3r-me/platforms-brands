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
            @php
                $cbbCanUpdate = auth()->user()?->can('update', $contentBriefBoard);
                $cbbCanDelete = auth()->user()?->can('delete', $contentBriefBoard);
            @endphp
            @if($cbbCanUpdate && $cbbCanDelete)
                <x-nx-dropdown>
                    <x-nx-dropdown-item wire:click="startEditing">@svg('heroicon-o-pencil', 'w-4 h-4') Bearbeiten</x-nx-dropdown-item>
                    <x-nx-dropdown-divider />
                    <x-nx-dropdown-item wire:click="deleteBoard" wire:confirm="Content Brief wirklich löschen?" variant="danger">@svg('heroicon-o-trash', 'w-4 h-4') Löschen</x-nx-dropdown-item>
                </x-nx-dropdown>
            @elseif($cbbCanUpdate)
                <x-nx-button variant="ghost" size="sm" wire:click="startEditing">
                    @svg('heroicon-o-pencil', 'w-4 h-4') Bearbeiten
                </x-nx-button>
            @elseif($cbbCanDelete)
                <x-nx-button variant="ghost" size="sm" wire:click="deleteBoard" wire:confirm="Content Brief wirklich löschen?">
                    @svg('heroicon-o-trash', 'w-4 h-4') Löschen
                </x-nx-button>
            @endif
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        @include('brands::partials.board-sidebar', ['sidebarTitle' => 'Brief-Übersicht', 'detailRows' => array_filter([
            ['label' => 'Status', 'value' => $statuses[$contentBriefBoard->status] ?? $contentBriefBoard->status],
            ['label' => 'Content-Typ', 'value' => $contentTypes[$contentBriefBoard->content_type] ?? $contentBriefBoard->content_type],
            ['label' => 'Search Intent', 'value' => $searchIntents[$contentBriefBoard->search_intent] ?? $contentBriefBoard->search_intent],
            $contentBriefBoard->target_slug ? ['label' => 'Ziel-URL', 'value' => $contentBriefBoard->target_slug] : null,
            $contentBriefBoard->target_word_count ? ['label' => 'Ziel-Wortanzahl', 'value' => number_format($contentBriefBoard->target_word_count, 0, ',', '.')] : null,
            $contentBriefBoard->seoBoard ? ['label' => 'SEO Board', 'value' => $contentBriefBoard->seoBoard->name] : null,
            ['label' => 'Erstellt', 'value' => $contentBriefBoard->created_at->format('d.m.Y H:i')],
        ])])
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Status-Umschaltung --}}
        @can('update', $contentBriefBoard)
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach($statuses as $key => $label)
                    <button type="button" wire:click="updateStatus('{{ $key }}')"
                        class="rounded-full px-2.5 py-1 text-xs font-medium transition-colors
                            {{ $contentBriefBoard->status === $key
                                ? 'bg-[color:var(--nx-accent)] text-[color:var(--nx-on-accent)]'
                                : 'bg-[color:var(--nx-hover)] text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)]' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        @endcan

        {{-- Edit Form --}}
        @if($editing)
            <x-nx-section icon="heroicon-o-pencil-square" title="Content Brief bearbeiten">
                <form wire:submit="saveEditing" class="space-y-4">
                    <x-nx-input-text name="editName" label="Name / H1-Kandidat" wire:model="editName" :errorKey="'editName'" />
                    <x-nx-input-textarea name="editDescription" label="Beschreibung" :rows="3" wire:model="editDescription" />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-nx-input-select name="editContentType" label="Content-Typ" wire:model="editContentType" :options="$contentTypes" />
                        <x-nx-input-select name="editSearchIntent" label="Search Intent" wire:model="editSearchIntent" :options="$searchIntents" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-nx-input-select name="editStatus" label="Status" wire:model="editStatus" :options="$statuses" />
                        <x-nx-input-number name="editTargetWordCount" label="Ziel-Wortanzahl" :min="0" wire:model="editTargetWordCount" />
                    </div>
                    <x-nx-input-text name="editTargetSlug" label="Ziel-URL / Slug" wire:model="editTargetSlug" placeholder="/blog/mein-artikel" />
                    <div class="flex items-center gap-3 pt-1">
                        <x-nx-button type="submit" variant="primary" size="sm">Speichern</x-nx-button>
                        <x-nx-button type="button" variant="ghost" size="sm" wire:click="cancelEditing">Abbrechen</x-nx-button>
                    </div>
                </form>
            </x-nx-section>
        @endif

        {{-- Titel --}}
        <div>
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $contentBriefBoard->name }}</h1>
                @php
                    $cbbStatusVariant = [
                        'draft' => 'neutral',
                        'briefed' => 'info',
                        'in_production' => 'warning',
                        'review' => 'accent',
                        'published' => 'success',
                    ][$contentBriefBoard->status] ?? 'neutral';
                @endphp
                <x-nx-badge :variant="$cbbStatusVariant">{{ $statuses[$contentBriefBoard->status] ?? $contentBriefBoard->status }}</x-nx-badge>
            </div>
            @if($contentBriefBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $contentBriefBoard->description }}</p>
            @endif
        </div>

        {{-- Metadaten --}}
        <x-nx-section icon="heroicon-o-clipboard-document-list" title="Metadaten">
            <x-nx-card flush>
                <div class="divide-y divide-[color:var(--nx-line)]">
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <span class="text-[13px] text-[color:var(--nx-faint)]">Content-Typ</span>
                        <span class="text-[13px] font-medium text-[color:var(--nx-text)]">{{ $contentTypes[$contentBriefBoard->content_type] ?? $contentBriefBoard->content_type }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <span class="text-[13px] text-[color:var(--nx-faint)]">Search Intent</span>
                        <span class="text-[13px] font-medium text-[color:var(--nx-text)]">{{ $searchIntents[$contentBriefBoard->search_intent] ?? $contentBriefBoard->search_intent }}</span>
                    </div>
                    @if($contentBriefBoard->target_word_count)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <span class="text-[13px] text-[color:var(--nx-faint)]">Ziel-Wortanzahl</span>
                            <span class="text-[13px] font-medium tabular-nums text-[color:var(--nx-text)]">{{ number_format($contentBriefBoard->target_word_count, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($contentBriefBoard->target_slug)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <span class="shrink-0 text-[13px] text-[color:var(--nx-faint)]">Ziel-URL</span>
                            <span class="min-w-0 truncate text-right text-[13px] font-medium text-[color:var(--nx-text)]">{{ $contentBriefBoard->target_slug }}</span>
                        </div>
                    @endif
                </div>
            </x-nx-card>

            @if($contentBriefBoard->seoBoard)
                <a href="{{ route('brands.seo-boards.show', $contentBriefBoard->seoBoard) }}"
                   class="mt-3 flex items-center gap-3 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-4 py-3 transition-colors hover:bg-[color:var(--nx-hover)]">
                    @svg('heroicon-o-magnifying-glass', 'w-5 h-5 text-[color:var(--nx-faint)]')
                    <div class="min-w-0">
                        <div class="text-[11px] font-medium uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Verknüpftes SEO Board</div>
                        <div class="truncate text-sm font-medium text-[color:var(--nx-text)]">{{ $contentBriefBoard->seoBoard->name }}</div>
                    </div>
                </a>
            @endif
        </x-nx-section>

    </x-ui-page-container>

    <x-slot name="activity">
        @include('brands::partials.board-activity')
    </x-slot>
</x-ui-page>
