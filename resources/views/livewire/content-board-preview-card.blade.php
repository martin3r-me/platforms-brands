@props(['contentBoard'])

<x-ui-kanban-card 
    :title="''" 
    :sortable-id="$contentBoard->id" 
    :href="route('brands.content-boards.show', $contentBoard)"
>
    <!-- Titel -->
    <div class="mb-3">
        <h4 class="text-sm font-medium text-[var(--ui-secondary)] m-0">
            {{ $contentBoard->name }}
        </h4>
    </div>

    <!-- Description -->
    @if($contentBoard->description)
        <div class="text-xs text-[var(--ui-muted)] my-1.5 mb-3 line-clamp-2">
            {{ Str::limit($contentBoard->description, 120) }}
        </div>
    @endif

    <!-- Meta: Blocks Count -->
    @if($contentBoard->blocks)
        <div class="mb-2">
            <span class="inline-flex items-center gap-1 text-xs text-[var(--ui-muted)]">
                @svg('heroicon-o-document-text','w-2.5 h-2.5')
                <span>{{ $contentBoard->blocks->count() }} {{ Str::plural('Block', $contentBoard->blocks->count()) }}</span>
            </span>
        </div>
    @endif
</x-ui-kanban-card>
