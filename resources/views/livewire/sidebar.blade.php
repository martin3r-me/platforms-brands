{{-- Brands Sidebar - Struktur nach Planner-Vorbild --}}
<div 
    x-data="{
        init() {
            // Zustand aus localStorage laden beim Initialisieren
            const savedState = localStorage.getItem('brands.showAllBrands');
            if (savedState !== null) {
                @this.set('showAllBrands', savedState === 'true');
            }
        }
    }"
>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Marken
    </div>
    
    {{-- Abschnitt: Allgemein (über UI-Komponenten) --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('brands.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        {{-- Weitere Items werden später hinzugefügt --}}
        {{-- <x-ui-sidebar-item :href="route('brands.xxx')">
            @svg('heroicon-o-xxx', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">XXX</span>
        </x-ui-sidebar-item> --}}
    </x-ui-sidebar-list>

    {{-- Neue Marke --}}
    <x-ui-sidebar-list>
        <x-ui-sidebar-item wire:click="createBrand">
            @svg('heroicon-o-plus-circle', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Neue Marke</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only für Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('brands.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            {{-- Weitere Icons werden später hinzugefügt --}}
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <button type="button" wire:click="createBrand" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Abschnitt: Marken --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            {{-- Marken nur anzeigen, wenn welche vorhanden sind --}}
            @if($brands->isNotEmpty())
                <x-ui-sidebar-list :label="'Marken' . ($showAllBrands ? ' (' . $allBrandsCount . ')' : '')">
                    @foreach($brands as $brand)
                        <x-ui-sidebar-item :href="route('brands.brands.show', ['brandsBrand' => $brand])">
                            @svg('heroicon-o-tag', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                            <div class="flex-1 min-w-0 ml-2">
                                <div class="truncate text-sm font-medium">{{ $brand->name }}</div>
                            </div>
                        </x-ui-sidebar-item>
                    @endforeach
                </x-ui-sidebar-list>
            @endif

            {{-- Button zum Ein-/Ausblenden aller Marken --}}
            @if($hasMoreBrands)
                <div class="px-3 py-2">
                    <button 
                        type="button" 
                        wire:click="toggleShowAllBrands"
                        x-on:click="localStorage.setItem('brands.showAllBrands', (!$wire.showAllBrands).toString())"
                        class="flex items-center gap-2 text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                    >
                        @if($showAllBrands)
                            @svg('heroicon-o-eye-slash', 'w-4 h-4')
                            <span>Nur meine Marken</span>
                        @else
                            @svg('heroicon-o-eye', 'w-4 h-4')
                            <span>Alle Marken anzeigen</span>
                        @endif
                    </button>
                </div>
            @endif

            {{-- Keine Marken --}}
            @if($brands->isEmpty())
                <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">
                    @if($showAllBrands)
                        Keine Marken
                    @else
                        Keine Marken vorhanden
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
