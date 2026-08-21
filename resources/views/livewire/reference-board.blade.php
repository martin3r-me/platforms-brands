<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$referenceBoard->name" icon="heroicon-o-link" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $referenceBoard->brand->name, 'href' => route('brands.brands.show', $referenceBoard->brand)],
            ['label' => $referenceBoard->name],
        ]">
            @can('update', $referenceBoard)
                <x-nx-button variant="primary" size="sm" x-data
                             @click="$dispatch('open-modal-reference', { referenceBoardId: {{ $referenceBoard->id }} })">
                    @svg('heroicon-o-plus', 'w-4 h-4') Referenz hinzufügen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $referenceBoard->name }}</h1>
            @if($referenceBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $referenceBoard->description }}</p>
            @else
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">Sammle Websites, die als Vorbild dienen – und solche, die es nicht tun. Mit Begründung als Grundlage für den ersten Entwurf.</p>
            @endif
        </div>

        @if($total === 0)
            <x-nx-empty icon="heroicon-o-link">
                Noch keine Referenzen. Füge Websites hinzu, die dir gefallen oder nicht gefallen – mit einer kurzen Begründung.
                @can('update', $referenceBoard)
                    <x-slot name="action">
                        <x-nx-button variant="secondary" size="sm" x-data
                                     @click="$dispatch('open-modal-reference', { referenceBoardId: {{ $referenceBoard->id }} })">
                            @svg('heroicon-o-plus', 'w-3.5 h-3.5') Erste Referenz
                        </x-nx-button>
                    </x-slot>
                @endcan
            </x-nx-empty>
        @else

            {{-- Gefällt uns --}}
            @if($liked->isNotEmpty())
                <x-nx-section icon="heroicon-o-hand-thumb-up" title="Gefällt uns" :hint="$liked->count() . ' ' . ($liked->count() === 1 ? 'Vorbild' : 'Vorbilder')">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($liked as $ref)
                            @include('brands::partials.reference-card', ['ref' => $ref, 'board' => $referenceBoard, 'aspectLabels' => $aspectLabels])
                        @endforeach
                    </div>
                </x-nx-section>
            @endif

            {{-- Gefällt uns nicht --}}
            @if($disliked->isNotEmpty())
                <x-nx-section icon="heroicon-o-hand-thumb-down" title="Gefällt uns nicht" :hint="$disliked->count() . ' ' . ($disliked->count() === 1 ? 'Gegenbeispiel' : 'Gegenbeispiele')">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($disliked as $ref)
                            @include('brands::partials.reference-card', ['ref' => $ref, 'board' => $referenceBoard, 'aspectLabels' => $aspectLabels])
                        @endforeach
                    </div>
                </x-nx-section>
            @endif

            {{-- Neutral --}}
            @if($neutral->isNotEmpty())
                <x-nx-section icon="heroicon-o-minus-circle" title="Neutral" :hint="$neutral->count() . ' ' . ($neutral->count() === 1 ? 'Notiz' : 'Notizen')">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($neutral as $ref)
                            @include('brands::partials.reference-card', ['ref' => $ref, 'board' => $referenceBoard, 'aspectLabels' => $aspectLabels])
                        @endforeach
                    </div>
                </x-nx-section>
            @endif

        @endif

    </x-ui-page-container>

    <x-slot name="sidebar">
        @include('brands::partials.board-sidebar', ['detailRows' => [
            ['label' => 'Typ', 'value' => 'Referenzen Board'],
            ['label' => 'Erstellt', 'value' => $referenceBoard->created_at->format('d.m.Y')],
            ['label' => 'Referenzen', 'value' => (string) $total],
            ['label' => 'Gefällt uns', 'value' => (string) $liked->count()],
            ['label' => 'Gefällt uns nicht', 'value' => (string) $disliked->count()],
        ]])
    </x-slot>


    <livewire:brands.reference-modal/>
</x-ui-page>
