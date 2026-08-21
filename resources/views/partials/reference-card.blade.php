{{-- Einzelne Website-Referenz als Karte. Erwartet: $ref, $board, $aspectLabels --}}
@php
    $dotClass = $ref->verdict === 'like'
        ? 'bg-emerald-500'
        : ($ref->verdict === 'dislike' ? 'bg-rose-500' : 'bg-[color:var(--nx-line-strong)]');
@endphp
<x-nx-card flush class="group overflow-hidden">
    <a href="{{ $ref->url }}" target="_blank" rel="noopener noreferrer"
       class="block aspect-[16/10] w-full overflow-hidden bg-[color:var(--nx-accent-soft)]">
        @if($ref->screenshot_url)
            <img src="{{ $ref->screenshot_url }}" alt="{{ $ref->host }}"
                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]" loading="lazy">
        @else
            <div class="flex h-full w-full items-center justify-center text-[color:var(--nx-faint)]">@svg('heroicon-o-globe-alt', 'w-8 h-8')</div>
        @endif
    </a>
    <div class="p-4">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="h-2 w-2 shrink-0 rounded-full {{ $dotClass }}"></span>
                    <a href="{{ $ref->url }}" target="_blank" rel="noopener noreferrer"
                       class="truncate text-[14px] font-medium text-[color:var(--nx-text)] hover:underline">{{ $ref->title ?: $ref->host }}</a>
                </div>
                <div class="mt-0.5 truncate text-[12px] text-[color:var(--nx-faint)]">{{ $ref->host }}@if($ref->industry) · {{ $ref->industry }}@endif</div>
            </div>
            @can('update', $board)
                <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                    <button type="button" x-data
                            @click="$dispatch('open-modal-reference', { referenceBoardId: {{ $board->id }}, referenceId: {{ $ref->id }} })"
                            class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]" title="Bearbeiten">
                        @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                    </button>
                    <button type="button" wire:click="deleteReference({{ $ref->id }})" wire:confirm="Referenz wirklich löschen?"
                            class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-rose-50 hover:text-rose-600" title="Löschen">
                        @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                    </button>
                </div>
            @endcan
        </div>
        @if($ref->reason)
            <p class="mt-2.5 text-[13px] leading-relaxed text-[color:var(--nx-muted)]">{{ $ref->reason }}</p>
        @endif
        @if(!empty($ref->aspects))
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach($ref->aspects as $aspect)
                    <span class="rounded-[6px] bg-[color:var(--nx-accent-soft)] px-2 py-0.5 text-[11.5px] text-[color:var(--nx-muted)]">{{ $aspectLabels[$aspect] ?? $aspect }}</span>
                @endforeach
            </div>
        @endif
    </div>
</x-nx-card>
