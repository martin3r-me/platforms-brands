<x-nx-modal size="md" model="modalShow" header="Marken-Einstellungen">
    @if($brand)
        <div class="space-y-4">
            {{-- Marken Name --}}
            @can('update', $brand)
                <x-nx-input-text
                    name="brand.name"
                    label="Markenname"
                    wire:model.live.debounce.500ms="brand.name"
                    placeholder="Markenname eingeben..."
                    required
                    :errorKey="'brand.name'"
                />
            @else
                <div class="flex items-center justify-between rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Markenname</span>
                    <span class="font-medium text-[color:var(--nx-text)]">{{ $brand->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $brand)
                <x-nx-input-textarea
                    name="brand.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="brand.description"
                    placeholder="Beschreibung der Marke eingeben..."
                    :errorKey="'brand.description'"
                />

                {{-- Design-Links (Erstentwurf) --}}
                <x-nx-input-text
                    name="brand.live_url"
                    label="Bestandsseite"
                    wire:model.live.debounce.500ms="brand.live_url"
                    placeholder="Aktuelle Website (falls online)…"
                    hint="IST-Zustand · Ausgangspunkt des Relaunches"
                    :errorKey="'brand.live_url'"
                />
                <x-nx-input-text
                    name="brand.wireframe_url"
                    label="Wireframe-Link"
                    wire:model.live.debounce.500ms="brand.wireframe_url"
                    placeholder="Link zum Wireframe…"
                    hint="Struktur-Entwurf für den Neustart · werkzeug-agnostisch (Figma, Claude-Artifact, Skizze)"
                    :errorKey="'brand.wireframe_url'"
                />
                <x-nx-input-text
                    name="brand.mockup_url"
                    label="Mockup-Link"
                    wire:model.live.debounce.500ms="brand.mockup_url"
                    placeholder="Link zum Mockup…"
                    hint="Bestehendes Design des Kunden (optional)"
                    :errorKey="'brand.mockup_url'"
                />
            @else
                <div class="flex items-start justify-between gap-3 rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Beschreibung</span>
                    <span class="text-right font-medium text-[color:var(--nx-text)]">{{ $brand->description ?? '–' }}</span>
                </div>
            @endcan
        </div>

        {{-- Marke abschließen --}}
        @can('update', $brand)
            @if(!$brand->done)
                <div class="border-t border-[color:var(--nx-line)] pt-4 mt-4">
                    <x-nx-button
                        variant="primary"
                        wire:click="markAsDone"
                        class="w-full"
                    >
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-check-circle','w-5 h-5')
                            <span>Marke abschließen</span>
                        </span>
                    </x-nx-button>
                </div>
            @else
                <div class="border-t border-[color:var(--nx-line)] pt-4 mt-4">
                    <div class="p-3 rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
                        <div class="flex items-center gap-2 text-[color:var(--nx-text)]">
                            @svg('heroicon-o-check-circle','w-5 h-5')
                            <span class="font-medium">Marke abgeschlossen</span>
                        </div>
                        @if($brand->done_at)
                            <p class="text-sm text-[color:var(--nx-faint)] mt-1">
                                Abgeschlossen am: {{ $brand->done_at->format('d.m.Y H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        @endcan

        {{-- Marke löschen --}}
        @can('delete', $brand)
            <div class="mt-4">
                <x-nx-button variant="danger" size="sm" wire:click="deleteBrand" wire:confirm="Wirklich löschen?">
                    @svg('heroicon-o-trash', 'w-4 h-4') Marke löschen
                </x-nx-button>
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($brand)
            @can('update', $brand)
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            @endcan
        @endif
    </x-slot>
</x-nx-modal>
