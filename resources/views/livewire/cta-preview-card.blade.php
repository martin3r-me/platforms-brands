@props(['cta'])

<x-ui-kanban-card :title="''">
    {{-- Inline-Editing oder Label --}}
    @if($this->editingCtaId === $cta->id)
        <div class="space-y-2 mb-2">
            <input type="text" wire:model="editingLabel"
                class="w-full text-sm font-medium px-2 py-1 border border-violet-300 rounded bg-white focus:outline-none focus:ring-1 focus:ring-violet-500"
                placeholder="CTA Label..."
                wire:keydown.enter="saveEditing"
                wire:keydown.escape="cancelEditing" />
            <textarea wire:model="editingDescription"
                class="w-full text-xs px-2 py-1 border border-violet-300 rounded bg-white focus:outline-none focus:ring-1 focus:ring-violet-500 resize-none"
                rows="2"
                placeholder="Beschreibung..."
                wire:keydown.escape="cancelEditing"></textarea>
            <div class="flex gap-1">
                <button wire:click="saveEditing" class="px-2 py-0.5 text-[10px] font-medium bg-violet-600 text-white rounded hover:bg-violet-700">
                    Speichern
                </button>
                <button wire:click="cancelEditing" class="px-2 py-0.5 text-[10px] font-medium text-[var(--ui-muted)] border border-[var(--ui-border)]/40 rounded hover:bg-[var(--ui-muted-5)]">
                    Abbrechen
                </button>
            </div>
        </div>
    @else
        <div class="mb-2 group cursor-pointer" @can('update', $this->ctaBoard) wire:click="startEditing({{ $cta->id }})" @endcan>
            <h4 class="text-sm font-medium text-[var(--ui-secondary)] m-0 group-hover:text-violet-700 transition-colors">
                {{ $cta->label }}
            </h4>
            @if($cta->description)
                <p class="text-[10px] text-[var(--ui-muted)] mt-0.5 line-clamp-2">
                    {{ Str::limit($cta->description, 80) }}
                </p>
            @endif
        </div>
    @endif

    {{-- Badges: Type + Funnel Stage --}}
    <div class="flex flex-wrap gap-1.5 mb-2">
        {{-- Type Badge --}}
        @php
            $typeColors = [
                'primary' => 'violet',
                'secondary' => 'sky',
                'micro' => 'gray',
            ];
            $typeColor = $typeColors[$cta->type] ?? 'gray';
        @endphp
        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-{{ $typeColor }}-50 text-{{ $typeColor }}-700 text-[10px] font-medium border border-{{ $typeColor }}-200">
            {{ ucfirst($cta->type) }}
        </span>

        {{-- Funnel Stage Badge --}}
        @php
            $funnelColors = [
                'awareness' => 'blue',
                'consideration' => 'amber',
                'decision' => 'green',
            ];
            $funnelColor = $funnelColors[$cta->funnel_stage] ?? 'gray';
        @endphp
        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-{{ $funnelColor }}-50 text-{{ $funnelColor }}-700 text-[10px] font-medium border border-{{ $funnelColor }}-200">
            {{ ucfirst($cta->funnel_stage) }}
        </span>

        {{-- Active/Inactive Badge --}}
        @if(!$cta->is_active)
            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-50 text-red-700 text-[10px] font-medium border border-red-200">
                Inaktiv
            </span>
        @endif
    </div>

    {{-- Zielseite / URL --}}
    <div class="mb-2">
        @if($cta->target_page_id && $cta->targetPage)
            <div class="flex items-center gap-1 text-[10px] text-[var(--ui-muted)]">
                @svg('heroicon-o-document-text', 'w-3 h-3')
                <span class="truncate">{{ $cta->targetPage->title ?? $cta->targetPage->name ?? 'Seite #' . $cta->target_page_id }}</span>
            </div>
        @elseif($cta->target_url)
            <div class="flex items-center gap-1 text-[10px] text-[var(--ui-muted)]">
                @svg('heroicon-o-link', 'w-3 h-3')
                <span class="truncate">{{ Str::limit($cta->target_url, 40) }}</span>
            </div>
        @else
            <div class="flex items-center gap-1 text-[10px] text-[var(--ui-muted)] italic">
                @svg('heroicon-o-minus-circle', 'w-3 h-3')
                <span>Kein Ziel</span>
            </div>
        @endif
    </div>

    {{-- Performance Placeholder --}}
    <div class="border-t border-[var(--ui-border)]/30 pt-2 mt-2">
        <div class="flex items-center gap-3 text-[10px] text-[var(--ui-muted)]">
            <div class="flex items-center gap-1" title="Impressions">
                @svg('heroicon-o-eye', 'w-3 h-3')
                <span>--</span>
            </div>
            <div class="flex items-center gap-1" title="Clicks">
                @svg('heroicon-o-cursor-arrow-ripple', 'w-3 h-3')
                <span>--</span>
            </div>
            <div class="flex items-center gap-1" title="Conversion Rate">
                @svg('heroicon-o-chart-bar', 'w-3 h-3')
                <span>--</span>
            </div>
        </div>
        <div class="text-[9px] text-[var(--ui-muted)] mt-0.5 italic">
            Tracking noch nicht aktiv
        </div>
    </div>
</x-ui-kanban-card>
