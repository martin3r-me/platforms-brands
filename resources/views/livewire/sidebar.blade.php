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
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Marken
    </div>

    {{-- Abschnitt: Allgemein --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('brands.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
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
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <button type="button" wire:click="createBrand" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Abschnitt: Marken (Entity-basierte Gruppierung) --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            {{-- Entity Type Gruppen --}}
            @foreach($entityTypeGroups as $typeGroup)
                <x-ui-sidebar-list :label="$typeGroup['type_name']">
                    @foreach($typeGroup['entities'] as $entityGroup)
                        {{-- Entity mit aufklappbaren Marken --}}
                        <div x-data="{ open: localStorage.getItem('brands.entity.' + {{ $entityGroup['entity_id'] }}) === 'true' }"
                             class="flex flex-col">
                            <button type="button"
                                    @click="open = !open; localStorage.setItem('brands.entity.' + {{ $entityGroup['entity_id'] }}, open)"
                                    class="flex items-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition w-full text-left">
                                <span class="w-4 h-4 flex-shrink-0 flex items-center justify-center transition-transform"
                                      :class="open ? 'rotate-90' : ''">
                                    @svg('heroicon-o-chevron-right', 'w-3 h-3')
                                </span>
                                @php $icon = $typeGroup['type_icon'] ?? null; @endphp
                                @if($icon && str_starts_with($icon, 'heroicon-'))
                                    @svg($icon, 'w-4 h-4 flex-shrink-0 ml-1 text-[var(--ui-muted)]')
                                @else
                                    @svg('heroicon-o-rectangle-group', 'w-4 h-4 flex-shrink-0 ml-1 text-[var(--ui-muted)]')
                                @endif
                                <span class="ml-1.5 text-sm font-medium truncate">{{ $entityGroup['entity_name'] }}</span>
                                <span class="ml-auto text-xs text-[var(--ui-muted)]">{{ $entityGroup['brands']->count() }}</span>
                            </button>
                            <div x-show="open" x-collapse class="flex flex-col gap-0.5 pl-4">
                                @foreach($entityGroup['brands'] as $brand)
                                    <x-ui-sidebar-item :href="route('brands.brands.show', ['brandsBrand' => $brand])" :title="$brand->name">
                                        @svg('heroicon-o-tag', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                                        <div class="flex-1 min-w-0 ml-2">
                                            <span class="truncate text-sm font-medium">{{ $brand->name }}</span>
                                        </div>
                                    </x-ui-sidebar-item>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </x-ui-sidebar-list>
            @endforeach

            {{-- Unverknüpfte Marken --}}
            @if($unlinkedBrands->isNotEmpty())
                <x-ui-sidebar-list label="Unverknüpft">
                    @foreach($unlinkedBrands as $brand)
                        <x-ui-sidebar-item :href="route('brands.brands.show', ['brandsBrand' => $brand])" :title="$brand->name">
                            @svg('heroicon-o-tag', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                            <div class="flex-1 min-w-0 ml-2">
                                <span class="truncate text-sm font-medium">{{ $brand->name }}</span>
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
            @if($entityTypeGroups->isEmpty() && $unlinkedBrands->isEmpty())
                <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">
                    Keine Marken vorhanden
                </div>
            @endif
        </div>
    </div>
</div>
