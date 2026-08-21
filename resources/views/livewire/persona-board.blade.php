<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$personaBoard->name" icon="heroicon-o-user-group" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $personaBoard->brand->name, 'href' => route('brands.brands.show', $personaBoard->brand)],
            ['label' => $personaBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $personaBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-persona-board-settings', { personaBoardId: {{ $personaBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
            </x-slot>

            @can('update', $personaBoard)
                <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-persona', { personaBoardId: {{ $personaBoard->id }} })">
                    @svg('heroicon-o-plus', 'w-4 h-4') Persona hinzufügen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $personaBoard->name }}</h1>
            @if($personaBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $personaBoard->description }}</p>
            @endif
        </div>

        {{-- Personas --}}
        <x-nx-section icon="heroicon-o-users" title="Personas" :hint="(string) $personas->count()" description="Zielgruppen-Profile für die Markenkommunikation">
            @can('update', $personaBoard)
                <x-slot name="action">
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-persona', { personaBoardId: {{ $personaBoard->id }} })">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5') Persona
                    </x-nx-button>
                </x-slot>
            @endcan

            @if($personas->count() > 0)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($personas as $persona)
                        <x-nx-card hover class="group flex flex-col">
                            {{-- Kopf: Identität --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[color:var(--nx-accent-soft)] text-sm font-semibold text-[color:var(--nx-text)]">
                                        {{ strtoupper(substr($persona->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="truncate text-[15px] font-semibold text-[color:var(--nx-text)]">{{ $persona->name }}</h3>
                                        @if($persona->occupation)
                                            <p class="truncate text-[13px] text-[color:var(--nx-muted)]">{{ $persona->occupation }}</p>
                                        @endif
                                    </div>
                                </div>
                                @can('update', $personaBoard)
                                    <div class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button type="button" x-data @click="$dispatch('open-modal-persona', { personaBoardId: {{ $personaBoard->id }}, personaId: {{ $persona->id }} })"
                                                class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]" title="Bearbeiten">
                                            @svg('heroicon-o-pencil', 'w-4 h-4')
                                        </button>
                                        <button type="button" wire:click="deletePersona({{ $persona->id }})" wire:confirm="Persona wirklich löschen?"
                                                class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-danger)]" title="Löschen">
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                        </button>
                                    </div>
                                @endcan
                            </div>

                            {{-- Demografie-Badges --}}
                            @if($persona->age || $persona->gender)
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @if($persona->age)
                                        <x-nx-badge>{{ $persona->age }} Jahre</x-nx-badge>
                                    @endif
                                    @if($persona->gender)
                                        <x-nx-badge>{{ $persona->gender_label }}</x-nx-badge>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-4 space-y-4">
                                {{-- Demografie-Zeilen --}}
                                @if($persona->location || $persona->education || $persona->income_range)
                                    <div class="space-y-1.5">
                                        @if($persona->location)
                                            <div class="flex items-center gap-2 text-[13px]">
                                                @svg('heroicon-o-map-pin', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                                                <span class="text-[color:var(--nx-text)]">{{ $persona->location }}</span>
                                            </div>
                                        @endif
                                        @if($persona->education)
                                            <div class="flex items-center gap-2 text-[13px]">
                                                @svg('heroicon-o-academic-cap', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                                                <span class="text-[color:var(--nx-text)]">{{ $persona->education }}</span>
                                            </div>
                                        @endif
                                        @if($persona->income_range)
                                            <div class="flex items-center gap-2 text-[13px]">
                                                @svg('heroicon-o-banknotes', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                                                <span class="text-[color:var(--nx-text)]">{{ $persona->income_range }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Bio --}}
                                @if($persona->bio)
                                    <p class="text-[13px] italic leading-relaxed text-[color:var(--nx-muted)]">&ldquo;{{ Str::limit($persona->bio, 200) }}&rdquo;</p>
                                @endif

                                {{-- Pain Points --}}
                                @if(is_array($persona->pain_points) && count($persona->pain_points) > 0)
                                    <div>
                                        <h4 class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Pain Points</h4>
                                        <div class="space-y-1">
                                            @foreach(array_slice($persona->pain_points, 0, 3) as $point)
                                                <div class="flex items-start gap-2">
                                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-danger)]"></span>
                                                    <span class="text-[13px] text-[color:var(--nx-text)]">{{ $point['text'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                            @if(count($persona->pain_points) > 3)
                                                <span class="text-[11px] text-[color:var(--nx-faint)]">+{{ count($persona->pain_points) - 3 }} weitere</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Goals --}}
                                @if(is_array($persona->goals) && count($persona->goals) > 0)
                                    <div>
                                        <h4 class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Ziele</h4>
                                        <div class="space-y-1">
                                            @foreach(array_slice($persona->goals, 0, 3) as $goal)
                                                <div class="flex items-start gap-2">
                                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[color:var(--nx-success)]"></span>
                                                    <span class="text-[13px] text-[color:var(--nx-text)]">{{ $goal['text'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                            @if(count($persona->goals) > 3)
                                                <span class="text-[11px] text-[color:var(--nx-faint)]">+{{ count($persona->goals) - 3 }} weitere</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Quotes --}}
                                @if(is_array($persona->quotes) && count($persona->quotes) > 0)
                                    <div>
                                        <h4 class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Typische Zitate</h4>
                                        <div class="space-y-1.5">
                                            @foreach(array_slice($persona->quotes, 0, 2) as $quote)
                                                <p class="rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-[13px] italic text-[color:var(--nx-muted)]">&ldquo;{{ $quote['text'] ?? '' }}&rdquo;</p>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Behaviors --}}
                                @if(is_array($persona->behaviors) && count($persona->behaviors) > 0)
                                    <div>
                                        <h4 class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Verhalten</h4>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach(array_slice($persona->behaviors, 0, 4) as $behavior)
                                                <x-nx-badge variant="info">{{ $behavior['text'] ?? '' }}</x-nx-badge>
                                            @endforeach
                                            @if(count($persona->behaviors) > 4)
                                                <x-nx-badge>+{{ count($persona->behaviors) - 4 }}</x-nx-badge>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Channels --}}
                                @if(is_array($persona->channels) && count($persona->channels) > 0)
                                    <div>
                                        <h4 class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Kanäle</h4>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($persona->channels as $channel)
                                                <x-nx-badge variant="accent">{{ $channel['text'] ?? '' }}</x-nx-badge>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Fuß: Tone of Voice Verknüpfung --}}
                            @if($persona->toneOfVoiceBoard)
                                <div class="mt-4 flex items-center gap-2 border-t border-[color:var(--nx-line)] pt-3">
                                    @svg('heroicon-o-megaphone', 'w-4 h-4 shrink-0 text-[color:var(--nx-faint)]')
                                    <div class="min-w-0">
                                        <span class="block text-[10px] font-semibold uppercase tracking-[0.12em] text-[color:var(--nx-faint)]">Tone of Voice</span>
                                        <p class="truncate text-[13px] font-medium text-[color:var(--nx-text)]">{{ $persona->toneOfVoiceBoard->name }}</p>
                                    </div>
                                </div>
                            @endif
                        </x-nx-card>
                    @endforeach
                </div>
            @else
                <x-nx-empty icon="heroicon-o-user-group">
                    Noch keine Personas – erstelle Zielgruppen-Profile für die Markenkommunikation.
                    @can('update', $personaBoard)
                        <x-slot name="action">
                            <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-persona', { personaBoardId: {{ $personaBoard->id }} })">
                                @svg('heroicon-o-plus', 'w-4 h-4') Persona hinzufügen
                            </x-nx-button>
                        </x-slot>
                    @endcan
                </x-nx-empty>
            @endif
        </x-nx-section>
    </x-ui-page-container>

    <x-slot name="sidebar">
        @include('brands::partials.board-sidebar', ['detailRows' => [
            ['label' => 'Typ', 'value' => 'Personas'],
            ['label' => 'Erstellt', 'value' => $personaBoard->created_at->format('d.m.Y')],
            ['label' => 'Personas', 'value' => (string) $personas->count()],
        ]])
    </x-slot>


    <livewire:brands.persona-board-settings-modal />
    <livewire:brands.persona-modal />
</x-ui-page>
