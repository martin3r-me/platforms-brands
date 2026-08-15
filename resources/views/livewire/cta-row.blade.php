@props(['cta'])

{{-- Hairline-Zeile für einen CTA (nx). Logik identisch zur früheren cta-preview-card. --}}
<div class="group px-4 py-3 transition-colors hover:bg-[color:var(--nx-hover)]">
    @if($this->editingCtaId === $cta->id)
        {{-- Inline-Editing --}}
        <div class="space-y-2">
            <input type="text" wire:model="editingLabel"
                class="w-full rounded-[6px] border border-[color:var(--nx-accent)] bg-[color:var(--nx-surface)] px-2.5 py-1.5 text-sm font-medium text-[color:var(--nx-text)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]"
                placeholder="CTA Label..."
                wire:keydown.enter="saveEditing"
                wire:keydown.escape="cancelEditing" />
            <textarea wire:model="editingDescription"
                class="w-full resize-none rounded-[6px] border border-[color:var(--nx-accent)] bg-[color:var(--nx-surface)] px-2.5 py-1.5 text-xs text-[color:var(--nx-text)] focus:outline-none focus:ring-1 focus:ring-[color:var(--nx-accent)]"
                rows="2"
                placeholder="Beschreibung..."
                wire:keydown.escape="cancelEditing"></textarea>
            <div class="flex gap-2">
                <x-nx-button variant="primary" size="sm" wire:click="saveEditing">Speichern</x-nx-button>
                <x-nx-button variant="ghost" size="sm" wire:click="cancelEditing">Abbrechen</x-nx-button>
            </div>
        </div>
    @else
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1 @can('update', $this->ctaBoard) cursor-pointer @endcan" @can('update', $this->ctaBoard) wire:click="startEditing({{ $cta->id }})" @endcan>
                {{-- Label + Badges --}}
                <div class="flex flex-wrap items-center gap-2">
                    <h4 class="m-0 text-sm font-medium text-[color:var(--nx-text)]">{{ $cta->label }}</h4>
                    @php
                        $typeVariants = ['primary' => 'accent', 'secondary' => 'info', 'micro' => 'neutral'];
                        $funnelVariants = ['awareness' => 'info', 'consideration' => 'warning', 'decision' => 'success'];
                    @endphp
                    <x-nx-badge :variant="$typeVariants[$cta->type] ?? 'neutral'">{{ ucfirst($cta->type) }}</x-nx-badge>
                    <x-nx-badge :variant="$funnelVariants[$cta->funnel_stage] ?? 'neutral'">{{ ucfirst($cta->funnel_stage) }}</x-nx-badge>
                    @if(!$cta->is_active)
                        <x-nx-badge variant="danger">Inaktiv</x-nx-badge>
                    @endif
                </div>

                {{-- Beschreibung --}}
                @if($cta->description)
                    <p class="mt-1 line-clamp-2 text-[13px] text-[color:var(--nx-muted)]">{{ Str::limit($cta->description, 120) }}</p>
                @endif

                {{-- Ziel-URL + Metrics --}}
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-[color:var(--nx-faint)]">
                    @if($cta->target_url)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-link', 'w-3 h-3')
                            <span class="truncate">{{ Str::limit($cta->target_url, 40) }}</span>
                        </span>
                    @else
                        <span class="flex items-center gap-1 italic">
                            @svg('heroicon-o-minus-circle', 'w-3 h-3')
                            <span>Kein Ziel</span>
                        </span>
                    @endif
                    <span class="flex items-center gap-1" title="Impressions">
                        @svg('heroicon-o-eye', 'w-3 h-3')
                        <span>{{ number_format($cta->impressions ?? 0) }}</span>
                    </span>
                    <span class="flex items-center gap-1" title="Clicks">
                        @svg('heroicon-o-cursor-arrow-ripple', 'w-3 h-3')
                        <span>{{ number_format($cta->clicks ?? 0) }}</span>
                    </span>
                    <span class="flex items-center gap-1" title="Conversion Rate">
                        @svg('heroicon-o-chart-bar', 'w-3 h-3')
                        <span>{{ number_format($cta->conversion_rate * 100, 1) }}%</span>
                    </span>
                    @if($cta->impressions > 0 && $cta->last_clicked_at)
                        <span>Letzter Klick: {{ $cta->last_clicked_at->diffForHumans() }}</span>
                    @elseif($cta->impressions === 0)
                        <span class="italic">Noch keine Tracking-Daten</span>
                    @endif
                </div>
            </div>

            @can('update', $this->ctaBoard)
                <button type="button" wire:click="startEditing({{ $cta->id }})"
                    class="shrink-0 rounded p-1 text-[color:var(--nx-faint)] opacity-0 transition-all hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)] group-hover:opacity-100" title="Bearbeiten">
                    @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                </button>
            @endcan
        </div>
    @endif
</div>
