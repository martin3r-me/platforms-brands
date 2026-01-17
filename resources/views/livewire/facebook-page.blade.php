<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$facebookPage->name" icon="heroicon-o-globe-alt" />
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $facebookPage->name }}</h1>
                
                @if($facebookPage->description)
                    <div class="mt-4">
                        <p class="text-[var(--ui-secondary)]">{{ $facebookPage->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Content Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                @svg('heroicon-o-information-circle', 'w-8 h-8 text-[var(--ui-muted)]')
            </div>
            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Inhalte werden geladen</h3>
            <p class="text-sm text-[var(--ui-muted)]">Die Inhalte für diese Facebook Page werden in einem späteren Schritt implementiert.</p>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Facebook Page Details" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">External ID</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $facebookPage->external_id }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $facebookPage->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($facebookPage->brand)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Marke</span>
                                <a href="{{ route('brands.brands.show', $facebookPage->brand) }}" class="text-sm text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $facebookPage->brand->name }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
