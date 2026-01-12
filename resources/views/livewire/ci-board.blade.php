<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$ciBoard->name" icon="heroicon-o-paint-brush">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $ciBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zurück zur Marke</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Hero Header Section --}}
        <div class="relative overflow-hidden rounded-2xl border border-[var(--ui-border)]/60 shadow-lg">
            @if($ciBoard->primary_color || $ciBoard->colors->first())
                <div class="absolute inset-0 opacity-5" style="background: linear-gradient(135deg, {{ $ciBoard->primary_color ?? $ciBoard->colors->first()->color ?? '#6366f1' }} 0%, {{ $ciBoard->secondary_color ?? $ciBoard->colors->skip(1)->first()->color ?? '#8b5cf6' }} 100%);"></div>
            @endif
            <div class="relative p-8 lg:p-12 bg-gradient-to-br from-white via-white to-[var(--ui-muted-5)]">
                <div class="flex items-start justify-between gap-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[var(--ui-primary)] to-[var(--ui-primary-80)] flex items-center justify-center shadow-lg">
                                @svg('heroicon-o-paint-brush', 'w-6 h-6 text-white')
                            </div>
                            <div>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[var(--ui-primary-10)] border border-[var(--ui-primary)]/20 text-xs font-semibold text-[var(--ui-primary)]">
                                    @svg('heroicon-o-sparkles', 'w-3 h-3')
                                    Corporate Identity
                                </span>
                            </div>
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-bold text-[var(--ui-secondary)] mb-3 tracking-tight leading-tight">{{ $ciBoard->name }}</h1>
                        @if($ciBoard->description)
                            <p class="text-lg text-[var(--ui-muted)] leading-relaxed max-w-2xl">{{ $ciBoard->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Farbpalette Section --}}
        <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)] mb-1">Farbpalette</h2>
                        <p class="text-sm text-[var(--ui-muted)]">Die Farben deiner Marke</p>
                    </div>
                    @can('update', $ciBoard)
                        <x-ui-button 
                            variant="primary" 
                            size="sm" 
                            x-data 
                            @click="$dispatch('open-modal-ci-board-color', { ciBoardId: {{ $ciBoard->id }} })"
                        >
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus','w-4 h-4')
                                <span>Farbe hinzufügen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
                
                {{-- Dynamische Farben-Grid --}}
                @if($ciBoard->colors->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                        @foreach($ciBoard->colors as $color)
                            <div class="group relative overflow-hidden rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md transition-all duration-300 bg-white">
                                <div class="h-32 relative" style="background-color: {{ $color->color ?? '#000000' }}">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-bold text-[var(--ui-secondary)] mb-1 truncate">{{ $color->title }}</h3>
                                            <p class="text-xs font-mono text-[var(--ui-muted)]">{{ $color->color ?? 'N/A' }}</p>
                                        </div>
                                        @can('update', $ciBoard)
                                            <x-ui-button 
                                                variant="secondary-outline" 
                                                size="xs" 
                                                x-data 
                                                @click="$dispatch('open-modal-ci-board-color', { ciBoardId: {{ $ciBoard->id }}, colorId: {{ $color->id }} })"
                                                class="flex-shrink-0"
                                            >
                                                @svg('heroicon-o-pencil','w-3.5 h-3.5')
                                            </x-ui-button>
                                        @endcan
                                    </div>
                                    @if($color->description)
                                        <p class="text-xs text-[var(--ui-muted)] line-clamp-2">{{ $color->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted)] mb-4">
                            @svg('heroicon-o-paint-brush', 'w-8 h-8 text-[var(--ui-muted)]')
                        </div>
                        <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Farben hinzugefügt</p>
                        <p class="text-xs text-[var(--ui-muted)]">Erstelle deine erste Markenfarbe</p>
                    </div>
                @endif

                {{-- Legacy-Farben (kompakter) --}}
                @if($ciBoard->primary_color || $ciBoard->secondary_color || $ciBoard->accent_color)
                    <div class="pt-6 border-t border-[var(--ui-border)]/60">
                        <h4 class="text-xs font-semibold text-[var(--ui-muted)] mb-4 uppercase tracking-wider">Legacy-Farben</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @if($ciBoard->primary_color)
                                <div>
                                    <label class="block text-xs font-semibold text-[var(--ui-muted)] mb-2 uppercase tracking-wide">Primärfarbe</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" 
                                               wire:model.live="ciBoard.primary_color" 
                                               value="{{ $ciBoard->primary_color ?? '#000000' }}"
                                               class="w-12 h-12 rounded-lg border-2 border-[var(--ui-border)] cursor-pointer shadow-sm hover:shadow-md transition-shadow">
                                        <input type="text" 
                                               wire:model.live="ciBoard.primary_color" 
                                               value="{{ $ciBoard->primary_color ?? '' }}"
                                               placeholder="#000000"
                                               pattern="^#[0-9A-Fa-f]{6}$"
                                               class="flex-1 px-3 py-2 text-sm font-mono border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                                    </div>
                                </div>
                            @endif
                            
                            @if($ciBoard->secondary_color)
                                <div>
                                    <label class="block text-xs font-semibold text-[var(--ui-muted)] mb-2 uppercase tracking-wide">Sekundärfarbe</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" 
                                               wire:model.live="ciBoard.secondary_color" 
                                               value="{{ $ciBoard->secondary_color ?? '#000000' }}"
                                               class="w-12 h-12 rounded-lg border-2 border-[var(--ui-border)] cursor-pointer shadow-sm hover:shadow-md transition-shadow">
                                        <input type="text" 
                                               wire:model.live="ciBoard.secondary_color" 
                                               value="{{ $ciBoard->secondary_color ?? '' }}"
                                               placeholder="#000000"
                                               pattern="^#[0-9A-Fa-f]{6}$"
                                               class="flex-1 px-3 py-2 text-sm font-mono border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                                    </div>
                                </div>
                            @endif
                            
                            @if($ciBoard->accent_color)
                                <div>
                                    <label class="block text-xs font-semibold text-[var(--ui-muted)] mb-2 uppercase tracking-wide">Akzentfarbe</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" 
                                               wire:model.live="ciBoard.accent_color" 
                                               value="{{ $ciBoard->accent_color ?? '#000000' }}"
                                               class="w-12 h-12 rounded-lg border-2 border-[var(--ui-border)] cursor-pointer shadow-sm hover:shadow-md transition-shadow">
                                        <input type="text" 
                                               wire:model.live="ciBoard.accent_color" 
                                               value="{{ $ciBoard->accent_color ?? '' }}"
                                               placeholder="#000000"
                                               pattern="^#[0-9A-Fa-f]{6}$"
                                               class="flex-1 px-3 py-2 text-sm font-mono border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Typografie & Text Section --}}
        <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[var(--ui-primary)]/10 to-[var(--ui-primary)]/5 flex items-center justify-center">
                        @svg('heroicon-o-document-text', 'w-5 h-5 text-[var(--ui-primary)]')
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Typografie & Text</h2>
                        <p class="text-sm text-[var(--ui-muted)]">Slogan, Tagline und Schriftarten</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-[var(--ui-secondary)] mb-2 flex items-center gap-2">
                                @svg('heroicon-o-sparkles', 'w-4 h-4 text-[var(--ui-primary)]')
                                Slogan
                            </label>
                            <textarea wire:model="ciBoard.slogan" 
                                      rows="4"
                                      placeholder="Dein prägnanter Marken-Slogan..."
                                      class="w-full px-4 py-3 text-lg border border-[var(--ui-border)] rounded-xl focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent transition-all resize-none"></textarea>
                            <p class="mt-1 text-xs text-[var(--ui-muted)]">Der zentrale Slogan deiner Marke</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-[var(--ui-secondary)] mb-2 flex items-center gap-2">
                                @svg('heroicon-o-chat-bubble-left-right', 'w-4 h-4 text-[var(--ui-primary)]')
                                Tagline
                            </label>
                            <textarea wire:model="ciBoard.tagline" 
                                      rows="3"
                                      placeholder="Eine kurze, prägnante Beschreibung..."
                                      class="w-full px-4 py-3 border border-[var(--ui-border)] rounded-xl focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent transition-all resize-none"></textarea>
                            <p class="mt-1 text-xs text-[var(--ui-muted)]">Kurze Beschreibung oder Untertitel</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-[var(--ui-secondary)] mb-2 flex items-center gap-2">
                            @svg('heroicon-o-font', 'w-4 h-4 text-[var(--ui-primary)]')
                            Schriftart
                        </label>
                        <input type="text" 
                               wire:model="ciBoard.font_family" 
                               placeholder="z.B. Inter, Arial, Helvetica, Roboto..."
                               class="w-full px-4 py-3 border border-[var(--ui-border)] rounded-xl focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent transition-all">
                        <p class="mt-1 text-xs text-[var(--ui-muted)]">Die primäre Schriftart deiner Marke</p>
                        
                        @if($ciBoard->font_family)
                            <div class="mt-4 p-4 bg-[var(--ui-muted-5)] rounded-xl border border-[var(--ui-border)]/40">
                                <p class="text-xs font-semibold text-[var(--ui-muted)] mb-2 uppercase tracking-wide">Vorschau</p>
                                <p class="text-2xl font-bold" style="font-family: {{ $ciBoard->font_family }}, sans-serif;">{{ $ciBoard->name ?? 'Beispieltext' }}</p>
                                <p class="text-sm mt-2" style="font-family: {{ $ciBoard->font_family }}, sans-serif;">{{ $ciBoard->slogan ?? 'Dies ist eine Vorschau der Schriftart' }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Farb-Vorschau (Groß) --}}
        @if($ciBoard->colors->count() > 0 || $ciBoard->primary_color || $ciBoard->secondary_color || $ciBoard->accent_color)
            <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                <div class="p-6 lg:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[var(--ui-primary)]/10 to-[var(--ui-primary)]/5 flex items-center justify-center">
                            @svg('heroicon-o-eye', 'w-5 h-5 text-[var(--ui-primary)]')
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Farb-Vorschau</h2>
                            <p class="text-sm text-[var(--ui-muted)]">Visuelle Darstellung deiner Farbpalette</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach($ciBoard->colors as $color)
                            @if($color->color)
                                <div class="group relative aspect-square rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 border-2 border-[var(--ui-border)]/40" style="background-color: {{ $color->color }}">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                    <div class="absolute bottom-0 left-0 right-0 p-3 transform translate-y-full group-hover:translate-y-0 transition-transform">
                                        <p class="text-xs font-bold text-white drop-shadow-lg">{{ $color->title }}</p>
                                        <p class="text-xs font-mono text-white/80">{{ $color->color }}</p>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        @if($ciBoard->primary_color)
                            <div class="aspect-square rounded-xl shadow-md border-2 border-[var(--ui-border)]/40" style="background-color: {{ $ciBoard->primary_color }}"></div>
                        @endif
                        @if($ciBoard->secondary_color)
                            <div class="aspect-square rounded-xl shadow-md border-2 border-[var(--ui-border)]/40" style="background-color: {{ $ciBoard->secondary_color }}"></div>
                        @endif
                        @if($ciBoard->accent_color)
                            <div class="aspect-square rounded-xl shadow-md border-2 border-[var(--ui-border)]/40" style="background-color: {{ $ciBoard->accent_color }}"></div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $ciBoard)
                            @if($this->isDirty())
                                <x-ui-button variant="primary" size="sm" wire:click="save" class="w-full">
                                    <span class="inline-flex items-center gap-2">
                                        @svg('heroicon-o-check','w-4 h-4')
                                        <span>Speichern</span>
                                    </span>
                                </x-ui-button>
                            @endif
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-ci-board-settings', { ciBoardId: {{ $ciBoard->id }} })" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                                    <span>Einstellungen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                </div>

                {{-- Board-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Typ</span>
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-[var(--ui-primary-10)] text-[var(--ui-primary)] border border-[var(--ui-primary)]/20">
                                CI Board
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $ciBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($ciBoard->colors->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Farben</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $ciBoard->colors->count() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <livewire:brands.ci-board-settings-modal/>
    <livewire:brands.ci-board-color-modal/>
</x-ui-page>
