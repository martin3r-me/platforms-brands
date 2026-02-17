<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$personaBoard->name" icon="heroicon-o-user-group">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $personaBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zur&uuml;ck zur Marke</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-100 to-teal-50 flex items-center justify-center">
                        @svg('heroicon-o-user-group', 'w-6 h-6 text-teal-600')
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">{{ $personaBoard->name }}</h1>
                        @if($personaBoard->description)
                            <p class="text-[var(--ui-muted)] mt-1">{{ $personaBoard->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Personas Grid --}}
        <div>
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-100 to-teal-50 flex items-center justify-center">
                        @svg('heroicon-o-users', 'w-5 h-5 text-teal-600')
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Personas</h2>
                        <p class="text-sm text-[var(--ui-muted)]">Zielgruppen-Profile f&uuml;r die Markenkommunikation</p>
                    </div>
                </div>
                @can('update', $personaBoard)
                    <x-ui-button
                        variant="primary"
                        size="sm"
                        x-data
                        @click="$dispatch('open-modal-persona', { personaBoardId: {{ $personaBoard->id }} })"
                    >
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Persona hinzuf&uuml;gen</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>

            @if($personas->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($personas as $persona)
                        <div class="group relative bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-lg hover:border-teal-200 transition-all duration-200 overflow-hidden">
                            {{-- Persona Header with Avatar --}}
                            <div class="bg-gradient-to-br from-teal-50 to-cyan-50 p-6 pb-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-4">
                                        {{-- Avatar --}}
                                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center text-white text-xl font-bold shadow-md flex-shrink-0">
                                            {{ strtoupper(substr($persona->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-[var(--ui-secondary)]">{{ $persona->name }}</h3>
                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                @if($persona->age)
                                                    <span class="text-xs text-[var(--ui-muted)] bg-white/70 px-2 py-0.5 rounded-full">{{ $persona->age }} Jahre</span>
                                                @endif
                                                @if($persona->gender)
                                                    <span class="text-xs text-[var(--ui-muted)] bg-white/70 px-2 py-0.5 rounded-full">{{ $persona->gender_label }}</span>
                                                @endif
                                            </div>
                                            @if($persona->occupation)
                                                <p class="text-sm font-medium text-teal-700 mt-1">{{ $persona->occupation }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @can('update', $personaBoard)
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                x-data
                                                @click="$dispatch('open-modal-persona', { personaBoardId: {{ $personaBoard->id }}, personaId: {{ $persona->id }} })"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-white/80 rounded transition-colors"
                                                title="Bearbeiten"
                                            >
                                                @svg('heroicon-o-pencil', 'w-4 h-4')
                                            </button>
                                            <button
                                                wire:click="deletePersona({{ $persona->id }})"
                                                wire:confirm="Persona wirklich l&ouml;schen?"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-white/80 rounded transition-colors"
                                                title="L&ouml;schen"
                                            >
                                                @svg('heroicon-o-trash', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endcan
                                </div>
                            </div>

                            {{-- Persona Body --}}
                            <div class="p-6 space-y-4">
                                {{-- Demographics --}}
                                @if($persona->location || $persona->education || $persona->income_range)
                                    <div class="grid grid-cols-1 gap-2">
                                        @if($persona->location)
                                            <div class="flex items-center gap-2 text-sm">
                                                @svg('heroicon-o-map-pin', 'w-4 h-4 text-[var(--ui-muted)] flex-shrink-0')
                                                <span class="text-[var(--ui-secondary)]">{{ $persona->location }}</span>
                                            </div>
                                        @endif
                                        @if($persona->education)
                                            <div class="flex items-center gap-2 text-sm">
                                                @svg('heroicon-o-academic-cap', 'w-4 h-4 text-[var(--ui-muted)] flex-shrink-0')
                                                <span class="text-[var(--ui-secondary)]">{{ $persona->education }}</span>
                                            </div>
                                        @endif
                                        @if($persona->income_range)
                                            <div class="flex items-center gap-2 text-sm">
                                                @svg('heroicon-o-banknotes', 'w-4 h-4 text-[var(--ui-muted)] flex-shrink-0')
                                                <span class="text-[var(--ui-secondary)]">{{ $persona->income_range }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Bio --}}
                                @if($persona->bio)
                                    <div>
                                        <p class="text-sm text-[var(--ui-secondary)] leading-relaxed italic">&ldquo;{{ Str::limit($persona->bio, 200) }}&rdquo;</p>
                                    </div>
                                @endif

                                {{-- Pain Points --}}
                                @if($persona->pain_points && count($persona->pain_points) > 0)
                                    <div>
                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-red-600 mb-2 flex items-center gap-1.5">
                                            @svg('heroicon-o-exclamation-triangle', 'w-3.5 h-3.5')
                                            Pain Points
                                        </h4>
                                        <div class="space-y-1">
                                            @foreach(array_slice($persona->pain_points, 0, 3) as $point)
                                                <div class="flex items-start gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400 mt-1.5 flex-shrink-0"></span>
                                                    <span class="text-xs text-[var(--ui-secondary)]">{{ $point['text'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                            @if(count($persona->pain_points) > 3)
                                                <span class="text-[10px] text-[var(--ui-muted)]">+{{ count($persona->pain_points) - 3 }} weitere</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Goals --}}
                                @if($persona->goals && count($persona->goals) > 0)
                                    <div>
                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-green-600 mb-2 flex items-center gap-1.5">
                                            @svg('heroicon-o-flag', 'w-3.5 h-3.5')
                                            Ziele
                                        </h4>
                                        <div class="space-y-1">
                                            @foreach(array_slice($persona->goals, 0, 3) as $goal)
                                                <div class="flex items-start gap-2">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 mt-1.5 flex-shrink-0"></span>
                                                    <span class="text-xs text-[var(--ui-secondary)]">{{ $goal['text'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                            @if(count($persona->goals) > 3)
                                                <span class="text-[10px] text-[var(--ui-muted)]">+{{ count($persona->goals) - 3 }} weitere</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Quotes --}}
                                @if($persona->quotes && count($persona->quotes) > 0)
                                    <div>
                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-amber-600 mb-2 flex items-center gap-1.5">
                                            @svg('heroicon-o-chat-bubble-bottom-center-text', 'w-3.5 h-3.5')
                                            Typische Zitate
                                        </h4>
                                        @foreach(array_slice($persona->quotes, 0, 2) as $quote)
                                            <p class="text-xs text-[var(--ui-secondary)] italic bg-amber-50 rounded-lg px-3 py-2 border border-amber-100 mb-1">&ldquo;{{ $quote['text'] ?? '' }}&rdquo;</p>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Behaviors --}}
                                @if($persona->behaviors && count($persona->behaviors) > 0)
                                    <div>
                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-blue-600 mb-2 flex items-center gap-1.5">
                                            @svg('heroicon-o-finger-print', 'w-3.5 h-3.5')
                                            Verhalten
                                        </h4>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach(array_slice($persona->behaviors, 0, 4) as $behavior)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-medium border border-blue-100">{{ $behavior['text'] ?? '' }}</span>
                                            @endforeach
                                            @if(count($persona->behaviors) > 4)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-[var(--ui-muted-5)] text-[var(--ui-muted)] text-[10px]">+{{ count($persona->behaviors) - 4 }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Channels --}}
                                @if($persona->channels && count($persona->channels) > 0)
                                    <div>
                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-purple-600 mb-2 flex items-center gap-1.5">
                                            @svg('heroicon-o-signal', 'w-3.5 h-3.5')
                                            Kan&auml;le
                                        </h4>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($persona->channels as $channel)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-[10px] font-medium border border-purple-100">{{ $channel['text'] ?? '' }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Footer: Tone of Voice Linkage --}}
                            @if($persona->toneOfVoiceBoard)
                                <div class="px-6 pb-4">
                                    <div class="flex items-center gap-2 px-3 py-2 bg-violet-50 rounded-lg border border-violet-100">
                                        @svg('heroicon-o-megaphone', 'w-4 h-4 text-violet-600 flex-shrink-0')
                                        <div class="min-w-0">
                                            <span class="text-[10px] font-semibold text-violet-500 uppercase tracking-wider">Tone of Voice</span>
                                            <p class="text-xs font-medium text-violet-700 truncate">{{ $persona->toneOfVoiceBoard->name }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-teal-50 mb-4">
                        @svg('heroicon-o-user-group', 'w-8 h-8 text-teal-400')
                    </div>
                    <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Personas</p>
                    <p class="text-xs text-[var(--ui-muted)] mb-4">Erstelle Zielgruppen-Profile f&uuml;r die Markenkommunikation</p>
                    @can('update', $personaBoard)
                        <x-ui-button
                            variant="primary"
                            size="sm"
                            x-data
                            @click="$dispatch('open-modal-persona', { personaBoardId: {{ $personaBoard->id }} })"
                        >
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Persona hinzuf&uuml;gen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-&Uuml;bersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $personaBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zur&uuml;ck zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $personaBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-persona', { personaBoardId: {{ $personaBoard->id }} })"
                                class="w-full"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Persona hinzuf&uuml;gen</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-persona-board-settings', { personaBoardId: {{ $personaBoard->id }} })" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
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
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-teal-50 text-teal-600 border border-teal-200">
                                Personas
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $personaBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($personas->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Personas</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $personas->count() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tone of Voice Verknüpfungen --}}
                @if($personas->whereNotNull('tone_of_voice_board_id')->count() > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Tone of Voice Zuordnung</h3>
                        <div class="space-y-2">
                            @foreach($personas->whereNotNull('tone_of_voice_board_id') as $persona)
                                <div class="flex items-center gap-2 py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center text-white text-[9px] font-bold flex-shrink-0">
                                        {{ strtoupper(substr($persona->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-xs font-medium text-[var(--ui-secondary)] truncate block">{{ $persona->name }}</span>
                                    </div>
                                    @svg('heroicon-o-arrow-right', 'w-3 h-3 text-[var(--ui-muted)] flex-shrink-0')
                                    <div class="flex items-center gap-1 min-w-0">
                                        @svg('heroicon-o-megaphone', 'w-3 h-3 text-violet-500 flex-shrink-0')
                                        <span class="text-xs text-violet-600 truncate">{{ $persona->toneOfVoiceBoard->name }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivit&auml;ten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Letzte Aktivit&auml;ten</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                            <div class="text-sm font-medium text-[var(--ui-secondary)]">{{ $activity['title'] ?? 'Aktivit&auml;t' }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $activity['time'] ?? '' }}</div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                                @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivit&auml;ten</p>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">&Auml;nderungen werden hier angezeigt</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <livewire:brands.persona-board-settings-modal />
    <livewire:brands.persona-modal />
</x-ui-page>
