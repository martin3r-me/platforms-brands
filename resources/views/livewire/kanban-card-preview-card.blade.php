@props(['card'])

<x-nx-kanban-card
    :title="''"
    :sortable-id="$card->id"
    :href="route('brands.kanban-cards.show', $card)"
>
    <!-- Titel -->
    <div class="mb-3">
        <h4 class="m-0 text-sm font-medium text-[color:var(--nx-text)]">
            {{ $card->title }}
        </h4>
    </div>

    <!-- Description -->
    @if($card->description)
        <div class="my-1.5 mb-3 line-clamp-2 text-xs text-[color:var(--nx-muted)]">
            {{ Str::limit($card->description, 120) }}
        </div>
    @endif

    <!-- Meta: Slot -->
    @if($card->slot)
        <div class="mb-2">
            <span class="inline-flex min-w-0 items-start gap-1 text-xs text-[color:var(--nx-faint)]">
                @svg('heroicon-o-view-columns','w-2.5 h-2.5 mt-0.5')
                <span class="max-w-[9rem] truncate">{{ $card->slot->name }}</span>
            </span>
        </div>
    @endif
</x-nx-kanban-card>
