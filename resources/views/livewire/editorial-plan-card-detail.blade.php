@props(['card'])

@php
    $statusVariants = [
        'draft' => 'neutral',
        'scheduled' => 'info',
        'publishing' => 'warning',
        'published' => 'success',
        'failed' => 'danger',
    ];
    $statusLabels = [
        'draft' => 'Entwurf',
        'scheduled' => 'Geplant',
        'publishing' => 'Wird veröffentlicht',
        'published' => 'Veröffentlicht',
        'failed' => 'Fehlgeschlagen',
    ];
    $statusVariant = $statusVariants[$card->status ?? 'draft'] ?? $statusVariants['draft'];
    $statusLabel = $statusLabels[$card->status ?? 'draft'] ?? 'Entwurf';

    // Collect unique platform keys from contracts
    $platformKeys = $card->contracts->map(function ($c) {
        return $c->platformFormat->platform->key ?? null;
    })->filter()->unique()->values();

    $platformIcons = [
        'facebook' => 'F',
        'instagram' => 'IG',
        'tiktok' => 'TT',
        'linkedin' => 'LI',
        'twitter' => 'X',
        'youtube' => 'YT',
        'pinterest' => 'P',
    ];
@endphp

<div x-data="{ expanded: false }" class="px-4 py-3">
    {{-- Card Row --}}
    <div class="flex items-center gap-3">
        {{-- Expand Toggle --}}
        <button @click="expanded = !expanded" class="flex-shrink-0 rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)]">
            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': expanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        {{-- Time --}}
        <div class="w-16 flex-shrink-0 text-xs font-medium text-[color:var(--nx-faint)]">
            @if($card->publish_at)
                {{ $card->publish_at->format('H:i') }}
            @else
                <span class="text-[color:var(--nx-faint)]/50">--:--</span>
            @endif
        </div>

        {{-- Title + Link --}}
        <a href="{{ route('brands.social-cards.show', $card) }}" class="min-w-0 flex-1 truncate text-sm font-medium text-[color:var(--nx-text)] transition-colors hover:text-[color:var(--nx-accent)]">
            {{ $card->title }}
        </a>

        {{-- Platform Icons --}}
        <div class="flex flex-shrink-0 items-center gap-1">
            @foreach($platformKeys as $key)
                <span class="inline-flex h-6 w-6 items-center justify-center rounded border border-[color:var(--nx-line)] bg-[color:var(--nx-hover)] text-[10px] font-bold text-[color:var(--nx-faint)]" title="{{ ucfirst($key) }}">
                    {{ $platformIcons[$key] ?? strtoupper(substr($key, 0, 2)) }}
                </span>
            @endforeach
        </div>

        {{-- Status Badge --}}
        <x-nx-badge :variant="$statusVariant" class="flex-shrink-0">{{ $statusLabel }}</x-nx-badge>

        {{-- Contracts count --}}
        <span class="flex-shrink-0 text-xs text-[color:var(--nx-faint)]" title="Contracts">
            {{ $card->contracts->count() }}C
        </span>
    </div>

    {{-- Expanded Detail --}}
    <div x-show="expanded" x-collapse class="mt-3 ml-9 space-y-3">
        {{-- Inline publish_at editing --}}
        <div class="flex items-center gap-3 rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-3">
            <span class="w-24 text-xs font-medium text-[color:var(--nx-faint)]">Publish at:</span>
            @can('update', $card)
                @if($editingCardId === $card->id)
                    <div class="flex flex-1 items-center gap-2">
                        <input
                            type="datetime-local"
                            wire:model="editPublishAt"
                            class="rounded-[6px] border border-[color:var(--nx-line-strong)] bg-[color:var(--nx-surface)] px-2 py-1 text-sm text-[color:var(--nx-text)] transition-colors focus:border-[color:var(--nx-accent)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]"
                        />
                        <button wire:click="savePublishAt" class="rounded-[6px] bg-[color:var(--nx-accent)] px-2 py-1 text-xs font-medium text-[color:var(--nx-on-accent)] transition-opacity hover:opacity-90">
                            Speichern
                        </button>
                        <button wire:click="cancelEditPublishAt" class="rounded-[6px] border border-[color:var(--nx-line-strong)] px-2 py-1 text-xs font-medium text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)]">
                            Abbrechen
                        </button>
                    </div>
                @else
                    <span class="text-sm text-[color:var(--nx-text)]">
                        {{ $card->publish_at?->format('d.m.Y H:i') ?? 'Nicht geplant' }}
                    </span>
                    <button wire:click="startEditPublishAt({{ $card->id }})" class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-accent)]" title="Bearbeiten">
                        @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                    </button>
                @endif
            @else
                <span class="text-sm text-[color:var(--nx-text)]">
                    {{ $card->publish_at?->format('d.m.Y H:i') ?? 'Nicht geplant' }}
                </span>
            @endcan
        </div>

        {{-- Contracts List --}}
        @if($card->contracts->count() > 0)
            <div class="space-y-1.5">
                <span class="text-xs font-medium text-[color:var(--nx-faint)]">Contracts:</span>
                @foreach($card->contracts as $contract)
                    @php
                        $contractStatusVariants = [
                            'draft' => 'neutral',
                            'ready' => 'info',
                            'published' => 'success',
                            'failed' => 'danger',
                        ];
                        $contractStatusLabels = [
                            'draft' => 'Entwurf',
                            'ready' => 'Bereit',
                            'published' => 'Veröffentlicht',
                            'failed' => 'Fehlgeschlagen',
                        ];
                        $cVariant = $contractStatusVariants[$contract->status] ?? $contractStatusVariants['draft'];
                        $cLabel = $contractStatusLabels[$contract->status] ?? $contract->status;
                        $platform = $contract->platformFormat->platform ?? null;
                        $format = $contract->platformFormat ?? null;
                    @endphp
                    <div class="flex items-center gap-2 rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-[color:var(--nx-hover)] text-[9px] font-bold text-[color:var(--nx-faint)]">
                            {{ $platformIcons[$platform->key ?? ''] ?? '?' }}
                        </span>
                        <span class="flex-1 truncate text-xs font-medium text-[color:var(--nx-text)]">
                            {{ $platform->name ?? '?' }} &middot; {{ $format->name ?? '?' }}
                        </span>
                        <x-nx-badge :variant="$cVariant">{{ $cLabel }}</x-nx-badge>
                        @if($contract->published_at)
                            <span class="text-[10px] text-[color:var(--nx-faint)]">{{ $contract->published_at->format('d.m. H:i') }}</span>
                        @endif
                        @if($contract->error_message)
                            <span class="max-w-[150px] truncate text-[10px] text-[color:var(--nx-danger)]" title="{{ $contract->error_message }}">{{ Str::limit($contract->error_message, 30) }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-3 text-xs italic text-[color:var(--nx-faint)]">
                Noch keine Contracts generiert.
            </div>
        @endif

        {{-- Actions --}}
        @can('update', $card)
            <div class="flex items-center gap-2 pt-1">
                <a href="{{ route('brands.social-cards.show', $card) }}" class="inline-flex items-center gap-1.5 rounded-[6px] border border-[color:var(--nx-line-strong)] px-3 py-1.5 text-xs font-medium text-[color:var(--nx-text)] transition-colors hover:bg-[color:var(--nx-hover)]">
                    @svg('heroicon-o-pencil-square', 'w-3.5 h-3.5')
                    Bearbeiten
                </a>
                @if($card->contracts->where('status', 'ready')->count() > 0 && !in_array($card->status, ['published', 'publishing']))
                    <button
                        wire:click="publishNow({{ $card->id }})"
                        wire:confirm="Alle ready Contracts dieser Card jetzt publishen?"
                        class="inline-flex items-center gap-1.5 rounded-[6px] bg-[color:var(--nx-accent)] px-3 py-1.5 text-xs font-medium text-[color:var(--nx-on-accent)] transition-opacity hover:opacity-90"
                    >
                        @svg('heroicon-o-paper-airplane', 'w-3.5 h-3.5')
                        Jetzt publishen
                    </button>
                @endif
            </div>
        @endcan
    </div>
</div>
