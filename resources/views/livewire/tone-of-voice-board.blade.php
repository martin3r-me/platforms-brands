<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$toneOfVoiceBoard->name" icon="heroicon-o-chat-bubble-bottom-center-text" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Marken', 'href' => route('brands.dashboard'), 'icon' => 'tag'],
            ['label' => $toneOfVoiceBoard->brand->name, 'href' => route('brands.brands.show', $toneOfVoiceBoard->brand)],
            ['label' => $toneOfVoiceBoard->name],
        ]">
            <x-slot name="left">
                @can('update', $toneOfVoiceBoard)
                    <x-nx-button variant="ghost" size="sm" x-data @click="$dispatch('open-modal-tone-of-voice-board-settings', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })">
                        @svg('heroicon-o-cog-6-tooth', 'w-4 h-4') Einstellungen
                    </x-nx-button>
                @endcan
            </x-slot>

            @can('update', $toneOfVoiceBoard)
                <x-nx-button variant="primary" size="sm" x-data @click="$dispatch('open-modal-tone-of-voice-entry', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })">
                    @svg('heroicon-o-plus', 'w-4 h-4') Eintrag hinzufügen
                </x-nx-button>
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-10" width="contained">

        {{-- Titel --}}
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-[color:var(--nx-text)]">{{ $toneOfVoiceBoard->name }}</h1>
            @if($toneOfVoiceBoard->description)
                <p class="mt-1.5 max-w-2xl text-[14.5px] leading-relaxed text-[color:var(--nx-muted)]">{{ $toneOfVoiceBoard->description }}</p>
            @endif
        </div>

        {{-- Tone-Dimensionen --}}
        <x-nx-section icon="heroicon-o-adjustments-horizontal" title="Tone-Dimensionen" description="Wie klingt die Marke? Positionierung auf Skalen">
            @can('update', $toneOfVoiceBoard)
                <x-slot name="action">
                    <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-tone-of-voice-dimension', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5') Dimension
                    </x-nx-button>
                </x-slot>
            @endcan

            @if($dimensions->count() > 0)
                <div class="space-y-3">
                    @foreach($dimensions as $dimension)
                        <div class="group rounded-[8px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-[13px] font-medium text-[color:var(--nx-text)]">{{ $dimension->name }}</span>
                                @can('update', $toneOfVoiceBoard)
                                    <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button
                                            x-data
                                            @click="$dispatch('open-modal-tone-of-voice-dimension', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }}, dimensionId: {{ $dimension->id }} })"
                                            class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]"
                                            title="Bearbeiten"
                                        >
                                            @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                        </button>
                                        <button
                                            wire:click="deleteDimension({{ $dimension->id }})"
                                            wire:confirm="Tone-Dimension wirklich löschen?"
                                            class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-danger)]"
                                            title="Löschen"
                                        >
                                            @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                                        </button>
                                    </div>
                                @endcan
                            </div>

                            {{-- Slider --}}
                            <div class="flex items-center gap-4">
                                <span class="min-w-[80px] text-center text-[11px] font-medium text-[color:var(--nx-muted)]">{{ $dimension->label_left }}</span>
                                <div class="relative flex-1" x-data="{ value: {{ $dimension->value }} }">
                                    <div class="h-1.5 w-full rounded-full bg-[color:var(--nx-accent-soft)]"></div>
                                    <div class="absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-[color:var(--nx-accent)]" :style="'width: ' + value + '%; left: 0;'"></div>
                                    <div
                                        class="absolute top-1/2 h-4 w-4 -translate-y-1/2 cursor-pointer rounded-full border-2 border-[color:var(--nx-accent)] bg-[color:var(--nx-surface)] transition-transform hover:scale-110"
                                        :style="'left: calc(' + value + '% - 8px)'"
                                    ></div>
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        x-model="value"
                                        @change="$wire.updateDimensionValue({{ $dimension->id }}, value)"
                                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                        @can('update', $toneOfVoiceBoard)
                                        @else
                                            disabled
                                        @endcan
                                    >
                                </div>
                                <span class="min-w-[80px] text-center text-[11px] font-medium text-[color:var(--nx-muted)]">{{ $dimension->label_right }}</span>
                            </div>

                            @if($dimension->description)
                                <p class="mt-2 text-[11px] text-[color:var(--nx-faint)]">{{ $dimension->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <x-nx-empty icon="heroicon-o-adjustments-horizontal">
                    Noch keine Tone-Dimensionen – definiere, wie die Markenstimme klingt.
                    @can('update', $toneOfVoiceBoard)
                        <x-slot name="action">
                            <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-tone-of-voice-dimension', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5') Dimension hinzufügen
                            </x-nx-button>
                        </x-slot>
                    @endcan
                </x-nx-empty>
            @endif
        </x-nx-section>

        {{-- Messaging-Elemente --}}
        <x-nx-section icon="heroicon-o-chat-bubble-left-right" title="Messaging-Elemente" :hint="$entries->count()" description="Slogans, Kernbotschaften, Elevator Pitch, Werte, Claims">
            @can('update', $toneOfVoiceBoard)
                <x-slot name="action">
                    <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-tone-of-voice-entry', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5') Eintrag
                    </x-nx-button>
                </x-slot>
            @endcan

            @if($entries->count() > 0)
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($entries as $entry)
                        @php
                            $typeVariant = [
                                'slogan' => 'info',
                                'elevator_pitch' => 'success',
                                'core_message' => 'warning',
                                'value' => 'accent',
                                'claim' => 'danger',
                            ][$entry->type] ?? 'neutral';
                        @endphp
                        <x-nx-card class="group">
                            {{-- Type Badge --}}
                            <div class="mb-3 flex items-start justify-between">
                                <x-nx-badge :variant="$typeVariant">{{ $entry->type_label }}</x-nx-badge>
                                @can('update', $toneOfVoiceBoard)
                                    <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <button
                                            x-data
                                            @click="$dispatch('open-modal-tone-of-voice-entry', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }}, entryId: {{ $entry->id }} })"
                                            class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-text)]"
                                            title="Bearbeiten"
                                        >
                                            @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                        </button>
                                        <button
                                            wire:click="deleteEntry({{ $entry->id }})"
                                            wire:confirm="Messaging-Eintrag wirklich löschen?"
                                            class="rounded p-1 text-[color:var(--nx-faint)] transition-colors hover:bg-[color:var(--nx-hover)] hover:text-[color:var(--nx-danger)]"
                                            title="Löschen"
                                        >
                                            @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                                        </button>
                                    </div>
                                @endcan
                            </div>

                            {{-- Name --}}
                            <h3 class="mb-2 text-[15px] font-semibold text-[color:var(--nx-text)]">{{ $entry->name }}</h3>

                            {{-- Content --}}
                            <p class="mb-3 text-[13px] leading-relaxed text-[color:var(--nx-text)]">{{ Str::limit($entry->content, 200) }}</p>

                            {{-- Description --}}
                            @if($entry->description)
                                <p class="mb-3 text-[11px] italic text-[color:var(--nx-faint)]">{{ Str::limit($entry->description, 100) }}</p>
                            @endif

                            {{-- Example Texts --}}
                            @if($entry->example_positive || $entry->example_negative)
                                <div class="mt-3 space-y-2 border-t border-[color:var(--nx-line)] pt-3">
                                    @if($entry->example_positive)
                                        <div class="flex items-start gap-2">
                                            <span class="mt-0.5 shrink-0 text-[color:var(--nx-success)]">
                                                @svg('heroicon-o-check', 'w-3.5 h-3.5')
                                            </span>
                                            <div>
                                                <span class="text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-success)]">So ja</span>
                                                <p class="text-[11px] leading-relaxed text-[color:var(--nx-text)]">{{ Str::limit($entry->example_positive, 120) }}</p>
                                            </div>
                                        </div>
                                    @endif
                                    @if($entry->example_negative)
                                        <div class="flex items-start gap-2">
                                            <span class="mt-0.5 shrink-0 text-[color:var(--nx-danger)]">
                                                @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                                            </span>
                                            <div>
                                                <span class="text-[10px] font-semibold uppercase tracking-wider text-[color:var(--nx-danger)]">So nein</span>
                                                <p class="text-[11px] leading-relaxed text-[color:var(--nx-text)]">{{ Str::limit($entry->example_negative, 120) }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </x-nx-card>
                    @endforeach
                </div>
            @else
                <x-nx-empty icon="heroicon-o-chat-bubble-left-right">
                    Noch keine Messaging-Elemente – erstelle Slogans, Kernbotschaften und mehr.
                    @can('update', $toneOfVoiceBoard)
                        <x-slot name="action">
                            <x-nx-button variant="secondary" size="sm" x-data @click="$dispatch('open-modal-tone-of-voice-entry', { toneOfVoiceBoardId: {{ $toneOfVoiceBoard->id }} })">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5') Eintrag hinzufügen
                            </x-nx-button>
                        </x-slot>
                    @endcan
                </x-nx-empty>
            @endif
        </x-nx-section>

    </x-ui-page-container>

    <x-slot name="sidebar">
        @include('brands::partials.board-sidebar', ['detailRows' => array_values(array_filter([
            ['label' => 'Typ', 'value' => 'Tone of Voice'],
            ['label' => 'Erstellt', 'value' => $toneOfVoiceBoard->created_at->format('d.m.Y')],
            $entries->count() > 0 ? ['label' => 'Einträge', 'value' => (string) $entries->count()] : null,
            $dimensions->count() > 0 ? ['label' => 'Dimensionen', 'value' => (string) $dimensions->count()] : null,
        ]))])
    </x-slot>


    <livewire:brands.tone-of-voice-board-settings-modal />
    <livewire:brands.tone-of-voice-entry-modal />
    <livewire:brands.tone-of-voice-dimension-modal />
</x-ui-page>
