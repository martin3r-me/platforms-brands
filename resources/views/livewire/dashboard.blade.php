<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Marken" icon="heroicon-o-tag" />
    </x-slot>

    <div class="p-6">
        @if($firstBrand)
            <div class="mb-4">
                <p class="text-[var(--ui-secondary)] mb-4">Weiterleitung zur ersten Marke...</p>
                <script>
                    window.location.href = '{{ route("brands.brands.show", $firstBrand) }}';
                </script>
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-[var(--ui-muted)] mb-4">Noch keine Marken vorhanden.</p>
                <p class="text-sm text-[var(--ui-muted)]">Marken können hier erstellt werden.</p>
            </div>
        @endif
    </div>
</x-ui-page>
