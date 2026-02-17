<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$toneOfVoiceBoard->name" icon="heroicon-o-chat-bubble-bottom-center-text">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $toneOfVoiceBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zurück zur Marke</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-100 to-violet-50 flex items-center justify-center">
                        @svg('heroicon-o-chat-bubble-bottom-center-text', 'w-6 h-6 text-violet-600')
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">{{ $toneOfVoiceBoard->name }}</h1>
                        @if($toneOfVoiceBoard->description)
                            <p class="text-[var(--ui-muted)] mt-1">{{ $toneOfVoiceBoard->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tone Slider Section --}}
        <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-violet-100 to-violet-50 flex items-center justify-center">
                            @svg('heroicon-o-adjustments-horizontal', 'w-5 h-5 text-violet-600')
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Tone-Dimensionen</h2>
                            <p class="text-sm text-[var(--ui-muted)]">Wie klingt die Marke? Positionierung auf Skalen</p>
                        </div>
                    </div>
                    @can('update', $toneOfVoiceBoard)
                        <x-ui-button
                            variant="primary"
                            size="sm"
                            x-data
                            @click="$dispatch('open-modal-tone-of-voice-dimension', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })"
                        >
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Dimension hinzufügen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>

                @if($dimensions->count() > 0)
                    <div class="space-y-6">
                        @foreach($dimensions as $dimension)
                            <div class="group relative p-4 rounded-xl bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 hover:border-violet-200 transition-colors">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $dimension->name }}</span>
                                    @can('update', $toneOfVoiceBoard)
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                x-data
                                                @click="$dispatch('open-modal-tone-of-voice-dimension', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }}, dimensionId: {{ $dimension->id }} })"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-[var(--ui-primary-5)] rounded transition-colors"
                                                title="Bearbeiten"
                                            >
                                                @svg('heroicon-o-pencil', 'w-4 h-4')
                                            </button>
                                            <button
                                                wire:click="deleteDimension({{ $dimension->id }})"
                                                wire:confirm="Tone-Dimension wirklich löschen?"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                                                title="Löschen"
                                            >
                                                @svg('heroicon-o-trash', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endcan
                                </div>

                                {{-- Slider --}}
                                <div class="flex items-center gap-4">
                                    <span class="text-xs font-medium text-violet-700 bg-violet-50 px-2 py-1 rounded-md border border-violet-200 min-w-[80px] text-center">{{ $dimension->label_left }}</span>
                                    <div class="flex-1 relative" x-data="{ value: {{ $dimension->value }} }">
                                        <div class="w-full h-2 bg-gradient-to-r from-violet-200 via-violet-100 to-violet-200 rounded-full"></div>
                                        <div class="absolute top-1/2 -translate-y-1/2 h-2 bg-violet-500 rounded-full" :style="'width: ' + value + '%; left: 0;'"></div>
                                        <div
                                            class="absolute top-1/2 -translate-y-1/2 w-5 h-5 bg-white border-2 border-violet-500 rounded-full shadow-sm cursor-pointer hover:scale-110 transition-transform"
                                            :style="'left: calc(' + value + '% - 10px)'"
                                        ></div>
                                        <input
                                            type="range"
                                            min="0"
                                            max="100"
                                            x-model="value"
                                            @change="$wire.updateDimensionValue({{ $dimension->id }}, value)"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            @can('update', $toneOfVoiceBoard)
                                            @else
                                                disabled
                                            @endcan
                                        >
                                    </div>
                                    <span class="text-xs font-medium text-violet-700 bg-violet-50 px-2 py-1 rounded-md border border-violet-200 min-w-[80px] text-center">{{ $dimension->label_right }}</span>
                                </div>

                                @if($dimension->description)
                                    <p class="text-xs text-[var(--ui-muted)] mt-2">{{ $dimension->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-violet-50 mb-4">
                            @svg('heroicon-o-adjustments-horizontal', 'w-7 h-7 text-violet-400')
                        </div>
                        <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Tone-Dimensionen</p>
                        <p class="text-xs text-[var(--ui-muted)] mb-4">Definiere, wie die Markenstimme klingt</p>
                        @can('update', $toneOfVoiceBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-tone-of-voice-dimension', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Dimension hinzufügen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                @endif
            </div>
        </div>

        {{-- Messaging Entries Section (Card-based) --}}
        <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-100 to-amber-50 flex items-center justify-center">
                            @svg('heroicon-o-chat-bubble-left-right', 'w-5 h-5 text-amber-600')
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Messaging-Elemente</h2>
                            <p class="text-sm text-[var(--ui-muted)]">Slogans, Kernbotschaften, Elevator Pitch, Werte, Claims</p>
                        </div>
                    </div>
                    @can('update', $toneOfVoiceBoard)
                        <x-ui-button
                            variant="primary"
                            size="sm"
                            x-data
                            @click="$dispatch('open-modal-tone-of-voice-entry', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })"
                        >
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Eintrag hinzufügen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>

                @if($entries->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($entries as $entry)
                            @php
                                $typeColors = [
                                    'slogan' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'hover' => 'hover:border-blue-300'],
                                    'elevator_pitch' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'hover' => 'hover:border-emerald-300'],
                                    'core_message' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'hover' => 'hover:border-amber-300'],
                                    'value' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'hover' => 'hover:border-purple-300'],
                                    'claim' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-200', 'hover' => 'hover:border-rose-300'],
                                ];
                                $colors = $typeColors[$entry->type] ?? $typeColors['core_message'];
                            @endphp
                            <div class="group relative bg-white rounded-xl border {{ $colors['border'] }} {{ $colors['hover'] }} shadow-sm hover:shadow-md transition-all p-5">
                                {{-- Type Badge --}}
                                <div class="flex items-start justify-between mb-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md {{ $colors['bg'] }} {{ $colors['text'] }} text-xs font-bold uppercase tracking-wider border {{ $colors['border'] }}">
                                        {{ $entry->type_label }}
                                    </span>
                                    @can('update', $toneOfVoiceBoard)
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                x-data
                                                @click="$dispatch('open-modal-tone-of-voice-entry', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }}, entryId: {{ $entry->id }} })"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-[var(--ui-primary-5)] rounded transition-colors"
                                                title="Bearbeiten"
                                            >
                                                @svg('heroicon-o-pencil', 'w-4 h-4')
                                            </button>
                                            <button
                                                wire:click="deleteEntry({{ $entry->id }})"
                                                wire:confirm="Messaging-Eintrag wirklich löschen?"
                                                class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                                                title="Löschen"
                                            >
                                                @svg('heroicon-o-trash', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endcan
                                </div>

                                {{-- Name --}}
                                <h3 class="text-base font-semibold text-[var(--ui-secondary)] mb-2">{{ $entry->name }}</h3>

                                {{-- Content --}}
                                <p class="text-sm text-[var(--ui-secondary)] leading-relaxed mb-3">{{ Str::limit($entry->content, 200) }}</p>

                                {{-- Description --}}
                                @if($entry->description)
                                    <p class="text-xs text-[var(--ui-muted)] mb-3 italic">{{ Str::limit($entry->description, 100) }}</p>
                                @endif

                                {{-- Example Texts --}}
                                @if($entry->example_positive || $entry->example_negative)
                                    <div class="border-t border-[var(--ui-border)]/40 pt-3 mt-3 space-y-2">
                                        @if($entry->example_positive)
                                            <div class="flex items-start gap-2">
                                                <span class="flex-shrink-0 inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 mt-0.5">
                                                    @svg('heroicon-o-check', 'w-3 h-3 text-green-600')
                                                </span>
                                                <div>
                                                    <span class="text-[10px] font-semibold text-green-600 uppercase tracking-wider">So ja</span>
                                                    <p class="text-xs text-[var(--ui-secondary)] leading-relaxed">{{ Str::limit($entry->example_positive, 120) }}</p>
                                                </div>
                                            </div>
                                        @endif
                                        @if($entry->example_negative)
                                            <div class="flex items-start gap-2">
                                                <span class="flex-shrink-0 inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-100 mt-0.5">
                                                    @svg('heroicon-o-x-mark', 'w-3 h-3 text-red-600')
                                                </span>
                                                <div>
                                                    <span class="text-[10px] font-semibold text-red-600 uppercase tracking-wider">So nein</span>
                                                    <p class="text-xs text-[var(--ui-secondary)] leading-relaxed">{{ Str::limit($entry->example_negative, 120) }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-50 mb-4">
                            @svg('heroicon-o-chat-bubble-left-right', 'w-8 h-8 text-amber-400')
                        </div>
                        <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Messaging-Elemente</p>
                        <p class="text-xs text-[var(--ui-muted)] mb-4">Erstelle Slogans, Kernbotschaften und mehr</p>
                        @can('update', $toneOfVoiceBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-tone-of-voice-entry', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Eintrag hinzufügen</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $toneOfVoiceBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurück zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex flex-col gap-2">
                        @can('update', $toneOfVoiceBoard)
                            <x-ui-button
                                variant="primary"
                                size="sm"
                                x-data
                                @click="$dispatch('open-modal-tone-of-voice-entry', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })"
                                class="w-full"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Eintrag hinzufügen</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-tone-of-voice-board-settings', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })" class="w-full">
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
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-violet-50 text-violet-600 border border-violet-200">
                                Tone of Voice
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $toneOfVoiceBoard->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($entries->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Einträge</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $entries->count() }}
                                </span>
                            </div>
                        @endif
                        @if($dimensions->count() > 0)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Dimensionen</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $dimensions->count() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Entry Types Overview --}}
                @if($entries->count() > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Nach Typ</h3>
                        <div class="space-y-2">
                            @foreach($entries->groupBy('type') as $type => $typeEntries)
                                <div class="flex items-center justify-between py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                    <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ \Platform\Brands\Models\BrandsToneOfVoiceEntry::TYPES[$type] ?? $type }}</span>
                                    <span class="text-xs font-medium px-1.5 py-0.5 rounded bg-violet-50 text-violet-600">{{ $typeEntries->count() }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Letzte Aktivitäten</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)]">
                            <div class="text-sm font-medium text-[var(--ui-secondary)]">{{ $activity['title'] ?? 'Aktivität' }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $activity['time'] ?? '' }}</div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                                @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivitäten</p>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">Änderungen werden hier angezeigt</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <livewire:brands.tone-of-voice-board-settings-modal />
    <livewire:brands.tone-of-voice-entry-modal />
    <livewire:brands.tone-of-voice-dimension-modal />
</x-ui-page>
