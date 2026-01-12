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

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $ciBoard->name }}</h1>
                
                @if($ciBoard->description)
                    <div class="mt-4">
                        <p class="text-[var(--ui-secondary)]">{{ $ciBoard->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- CI Board Content --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <h2 class="text-xl font-semibold text-[var(--ui-secondary)] mb-6">Corporate Identity</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Farben --}}
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--ui-secondary)] mb-4">Farben</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Primärfarbe</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" 
                                           wire:model.live="ciBoard.primary_color" 
                                           class="w-16 h-10 rounded border border-[var(--ui-border)] cursor-pointer">
                                    <input type="text" 
                                           wire:model.live="ciBoard.primary_color" 
                                           placeholder="#000000"
                                           class="flex-1 px-3 py-2 border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Sekundärfarbe</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" 
                                           wire:model.live="ciBoard.secondary_color" 
                                           class="w-16 h-10 rounded border border-[var(--ui-border)] cursor-pointer">
                                    <input type="text" 
                                           wire:model.live="ciBoard.secondary_color" 
                                           placeholder="#000000"
                                           class="flex-1 px-3 py-2 border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Akzentfarbe</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" 
                                           wire:model.live="ciBoard.accent_color" 
                                           class="w-16 h-10 rounded border border-[var(--ui-border)] cursor-pointer">
                                    <input type="text" 
                                           wire:model.live="ciBoard.accent_color" 
                                           placeholder="#000000"
                                           class="flex-1 px-3 py-2 border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Text & Slogan --}}
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--ui-secondary)] mb-4">Text & Slogan</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Slogan</label>
                                <textarea wire:model.live="ciBoard.slogan" 
                                          rows="3"
                                          placeholder="Dein Marken-Slogan..."
                                          class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Tagline</label>
                                <textarea wire:model.live="ciBoard.tagline" 
                                          rows="2"
                                          placeholder="Kurze Beschreibung..."
                                          class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">Schriftart</label>
                                <input type="text" 
                                       wire:model.live="ciBoard.font_family" 
                                       placeholder="z.B. Arial, Helvetica, ..."
                                       class="w-full px-3 py-2 border border-[var(--ui-border)] rounded-lg focus:ring-2 focus:ring-[var(--ui-primary)] focus:border-transparent">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Farb-Vorschau --}}
                @if($ciBoard->primary_color || $ciBoard->secondary_color || $ciBoard->accent_color)
                    <div class="mt-8 pt-6 border-t border-[var(--ui-border)]">
                        <h3 class="text-sm font-semibold text-[var(--ui-secondary)] mb-4">Farb-Vorschau</h3>
                        <div class="flex gap-2">
                            @if($ciBoard->primary_color)
                                <div class="flex-1 h-20 rounded-lg border border-[var(--ui-border)]" style="background-color: {{ $ciBoard->primary_color }}"></div>
                            @endif
                            @if($ciBoard->secondary_color)
                                <div class="flex-1 h-20 rounded-lg border border-[var(--ui-border)]" style="background-color: {{ $ciBoard->secondary_color }}"></div>
                            @endif
                            @if($ciBoard->accent_color)
                                <div class="flex-1 h-20 rounded-lg border border-[var(--ui-border)]" style="background-color: {{ $ciBoard->accent_color }}"></div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Board-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Typ</span>
                            <span class="text-xs font-medium px-2 py-0.5 rounded bg-[var(--ui-primary-5)] text-[var(--ui-primary)]">
                                CI Board
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $ciBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
