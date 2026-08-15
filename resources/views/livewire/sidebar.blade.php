{{-- Brands Sidebar - Organisationsstruktur-Gruppierung --}}
<div
    x-data="{
        init() {
            const savedState = localStorage.getItem('brands.showAllBrands');
            if (savedState !== null) {
                @this.set('showAllBrands', savedState === 'true');
            }
        }
    }"
>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[color:var(--nx-text)] uppercase border-b border-[color:var(--nx-line)] mb-2">
        Marken
    </div>

    {{-- Abschnitt: Allgemein --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('brands.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[color:var(--nx-text)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Neue Marke --}}
    <x-ui-sidebar-list>
        <x-ui-sidebar-item wire:click="createBrand">
            @svg('heroicon-o-plus-circle', 'w-4 h-4 text-[color:var(--nx-text)]')
            <span class="ml-2 text-sm">Neue Marke</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only für Allgemein --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[color:var(--nx-line)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('brands.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[color:var(--nx-line)]">
        <button type="button" wire:click="createBrand" class="flex items-center justify-center p-2 rounded-md text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Abschnitt: Marken (Entity-basierte Gruppierung) --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            {{-- Entity Type Gruppen (Baum-Darstellung) --}}
            @foreach($entityTypeGroups as $typeGroup)
                <x-ui-sidebar-list wire:key="type-group-{{ $typeGroup['type_id'] }}" :label="$typeGroup['type_name']">
                    @foreach($typeGroup['entities'] as $entityNode)
                        @include('brands::livewire.partials.sidebar-entity-node', [
                            'node' => $entityNode,
                            'typeIcon' => $typeGroup['type_icon'] ?? null,
                        ])
                    @endforeach
                </x-ui-sidebar-list>
            @endforeach

            {{-- Unverknüpfte Marken --}}
            @if($unlinkedBrands->isNotEmpty())
                <x-ui-sidebar-list label="Unverknüpft">
                    @foreach($unlinkedBrands as $brand)
                        <a wire:key="unlinked-brand-{{ $brand->id }}"
                           href="{{ route('brands.brands.show', ['brandsBrand' => $brand]) }}"
                           wire:navigate
                           title="{{ $brand->name }}"
                           class="flex items-center gap-1.5 py-0.5 pl-3 pr-2 text-[color:var(--nx-text)] hover:text-[color:var(--nx-accent)] transition truncate">
                            <span class="w-1 h-1 rounded-full flex-shrink-0 bg-[color:var(--nx-faint)] opacity-40"></span>
                            <span class="truncate text-[11px]">{{ $brand->name }}</span>
                        </a>
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
                        class="flex items-center gap-2 text-xs text-[color:var(--nx-faint)] hover:text-[color:var(--nx-text)] transition-colors"
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
            @if($entityTypeGroups->isEmpty() && $unlinkedBrands->isEmpty())
                <div class="px-3 py-1 text-xs text-[color:var(--nx-faint)]">
                    Keine Marken vorhanden
                </div>
            @endif
        </div>
    </div>
</div>
