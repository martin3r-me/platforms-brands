@props(['card'])

@php
    $statusColors = [
        'draft' => 'bg-gray-100 text-gray-600 border-gray-200',
        'scheduled' => 'bg-blue-50 text-blue-700 border-blue-200',
        'publishing' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        'published' => 'bg-green-50 text-green-700 border-green-200',
        'failed' => 'bg-red-50 text-red-700 border-red-200',
    ];
    $statusLabels = [
        'draft' => 'Entwurf',
        'scheduled' => 'Geplant',
        'publishing' => 'Wird veröffentlicht',
        'published' => 'Veröffentlicht',
        'failed' => 'Fehlgeschlagen',
    ];
    $statusColor = $statusColors[$card->status ?? 'draft'] ?? $statusColors['draft'];
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
        <button @click="expanded = !expanded" class="flex-shrink-0 p-1 rounded hover:bg-[var(--ui-muted-5)] transition-colors text-[var(--ui-muted)]">
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': expanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        {{-- Time --}}
        <div class="flex-shrink-0 w-16 text-xs font-medium text-[var(--ui-muted)]">
            @if($card->publish_at)
                {{ $card->publish_at->format('H:i') }}
            @else
                <span class="text-[var(--ui-muted)]/50">--:--</span>
            @endif
        </div>

        {{-- Title + Link --}}
        <a href="{{ route('brands.social-cards.show', $card) }}" class="flex-1 min-w-0 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] truncate transition-colors">
            {{ $card->title }}
        </a>

        {{-- Platform Icons --}}
        <div class="flex items-center gap-1 flex-shrink-0">
            @foreach($platformKeys as $key)
                <span class="inline-flex items-center justify-center w-6 h-6 rounded text-[10px] font-bold bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 text-[var(--ui-muted)]" title="{{ ucfirst($key) }}">
                    {{ $platformIcons[$key] ?? strtoupper(substr($key, 0, 2)) }}
                </span>
            @endforeach
        </div>

        {{-- Status Badge --}}
        <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider rounded-full border {{ $statusColor }}">
            {{ $statusLabel }}
        </span>

        {{-- Contracts count --}}
        <span class="flex-shrink-0 text-xs text-[var(--ui-muted)]" title="Contracts">
            {{ $card->contracts->count() }}C
        </span>
    </div>

    {{-- Expanded Detail --}}
    <div x-show="expanded" x-collapse class="mt-3 ml-9 space-y-3">
        {{-- Inline publish_at editing --}}
        <div class="flex items-center gap-3 p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/30">
            <span class="text-xs font-medium text-[var(--ui-muted)] w-24">Publish at:</span>
            @can('update', $card)
                @if($editingCardId === $card->id)
                    <div class="flex items-center gap-2 flex-1">
                        <input
                            type="datetime-local"
                            wire:model="editPublishAt"
                            class="text-sm rounded-lg border border-[var(--ui-border)] bg-white px-2 py-1 text-[var(--ui-secondary)] focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
                        />
                        <button wire:click="savePublishAt" class="px-2 py-1 text-xs font-medium rounded-md bg-[var(--ui-primary)] text-white hover:opacity-90 transition-opacity">
                            Speichern
                        </button>
                        <button wire:click="cancelEditPublishAt" class="px-2 py-1 text-xs font-medium rounded-md border border-[var(--ui-border)] text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] transition-colors">
                            Abbrechen
                        </button>
                    </div>
                @else
                    <span class="text-sm text-[var(--ui-secondary)]">
                        {{ $card->publish_at?->format('d.m.Y H:i') ?? 'Nicht geplant' }}
                    </span>
                    <button wire:click="startEditPublishAt({{ $card->id }})" class="p-1 rounded hover:bg-white transition-colors text-[var(--ui-muted)] hover:text-[var(--ui-primary)]" title="Bearbeiten">
                        @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                    </button>
                @endif
            @else
                <span class="text-sm text-[var(--ui-secondary)]">
                    {{ $card->publish_at?->format('d.m.Y H:i') ?? 'Nicht geplant' }}
                </span>
            @endcan
        </div>

        {{-- Contracts List --}}
        @if($card->contracts->count() > 0)
            <div class="space-y-1.5">
                <span class="text-xs font-medium text-[var(--ui-muted)]">Contracts:</span>
                @foreach($card->contracts as $contract)
                    @php
                        $contractStatusColors = [
                            'draft' => 'bg-gray-100 text-gray-500',
                            'ready' => 'bg-blue-50 text-blue-600',
                            'published' => 'bg-green-50 text-green-600',
                            'failed' => 'bg-red-50 text-red-600',
                        ];
                        $contractStatusLabels = [
                            'draft' => 'Entwurf',
                            'ready' => 'Bereit',
                            'published' => 'Veröffentlicht',
                            'failed' => 'Fehlgeschlagen',
                        ];
                        $cColor = $contractStatusColors[$contract->status] ?? $contractStatusColors['draft'];
                        $cLabel = $contractStatusLabels[$contract->status] ?? $contract->status;
                        $platform = $contract->platformFormat->platform ?? null;
                        $format = $contract->platformFormat ?? null;
                    @endphp
                    <div class="flex items-center gap-2 px-3 py-2 rounded-md bg-white border border-[var(--ui-border)]/30">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded text-[9px] font-bold bg-[var(--ui-muted-5)] text-[var(--ui-muted)]">
                            {{ $platformIcons[$platform->key ?? ''] ?? '?' }}
                        </span>
                        <span class="text-xs text-[var(--ui-secondary)] font-medium flex-1 truncate">
                            {{ $platform->name ?? '?' }} &middot; {{ $format->name ?? '?' }}
                        </span>
                        <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-semibold uppercase rounded {{ $cColor }}">
                            {{ $cLabel }}
                        </span>
                        @if($contract->published_at)
                            <span class="text-[10px] text-[var(--ui-muted)]">{{ $contract->published_at->format('d.m. H:i') }}</span>
                        @endif
                        @if($contract->error_message)
                            <span class="text-[10px] text-red-500 truncate max-w-[150px]" title="{{ $contract->error_message }}">{{ Str::limit($contract->error_message, 30) }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-xs text-[var(--ui-muted)] italic px-3">
                Noch keine Contracts generiert.
            </div>
        @endif

        {{-- Actions --}}
        @can('update', $card)
            <div class="flex items-center gap-2 pt-1">
                <a href="{{ route('brands.social-cards.show', $card) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-[var(--ui-border)] text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors">
                    @svg('heroicon-o-pencil-square', 'w-3.5 h-3.5')
                    Bearbeiten
                </a>
                @if($card->contracts->where('status', 'ready')->count() > 0 && !in_array($card->status, ['published', 'publishing']))
                    <button
                        wire:click="publishNow({{ $card->id }})"
                        wire:confirm="Alle ready Contracts dieser Card jetzt publishen?"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-[var(--ui-primary)] text-white hover:opacity-90 transition-opacity"
                    >
                        @svg('heroicon-o-paper-airplane', 'w-3.5 h-3.5')
                        Jetzt publishen
                    </button>
                @endif
            </div>
        @endcan
    </div>
</div>
