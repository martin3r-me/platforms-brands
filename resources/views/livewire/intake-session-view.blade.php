<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="'Session ' . $session->session_token" icon="heroicon-o-chat-bubble-left-right">
            <x-slot name="actions">
                <a href="{{ route('brands.intake-boards.show', $session->intakeBoard) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zurueck zum Board</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Header --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                            @svg('heroicon-o-chat-bubble-left-right', 'w-6 h-6 text-blue-600')
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">
                                Session {{ $session->session_token }}
                            </h1>
                            <p class="text-[var(--ui-muted)] mt-1">
                                {{ $session->intakeBoard->name }}
                            </p>
                        </div>
                    </div>

                    {{-- Status --}}
                    @if($session->status === 'completed')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 border border-green-200">
                            @svg('heroicon-o-check-circle', 'w-3.5 h-3.5')
                            Abgeschlossen
                        </span>
                    @elseif($session->status === 'in_progress')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 border border-amber-200">
                            @svg('heroicon-o-clock', 'w-3.5 h-3.5')
                            In Bearbeitung
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                            {{ $session->status ?? 'Unbekannt' }}
                        </span>
                    @endif
                </div>

                {{-- Meta-Info --}}
                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                        <span class="text-xs text-[var(--ui-muted)] block">Teilnehmer</span>
                        <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $session->respondent_name ?? '-' }}</span>
                    </div>
                    <div class="py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                        <span class="text-xs text-[var(--ui-muted)] block">E-Mail</span>
                        <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $session->respondent_email ?? '-' }}</span>
                    </div>
                    <div class="py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                        <span class="text-xs text-[var(--ui-muted)] block">Gestartet</span>
                        <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $session->started_at?->format('d.m.Y H:i') ?? '-' }}</span>
                    </div>
                    <div class="py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                        <span class="text-xs text-[var(--ui-muted)] block">Abgeschlossen</span>
                        <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $session->completed_at?->format('d.m.Y H:i') ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Antworten --}}
        <div>
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center">
                    @svg('heroicon-o-document-text', 'w-5 h-5 text-emerald-600')
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Antworten</h2>
                    <p class="text-sm text-[var(--ui-muted)]">Erhobene Daten pro Block</p>
                </div>
            </div>

            @if($blocks->count() > 0)
                <div class="space-y-4">
                    @foreach($blocks as $index => $block)
                        @php
                            $definition = $block->blockDefinition;
                            $blockKey = (string) $block->id;
                            $answer = $answers[$blockKey] ?? null;
                        @endphp
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                            {{-- Block Header --}}
                            <div class="px-6 py-4 bg-gradient-to-r from-emerald-50/50 to-slate-50/50 border-b border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center text-xs font-bold text-emerald-700 flex-shrink-0">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $definition->name ?? 'Block' }}</h3>
                                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                                {{ $definition->getBlockTypeLabel() ?? '' }}
                                            </span>
                                            @if($block->is_required)
                                                <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200">Pflicht</span>
                                            @endif
                                        </div>
                                        @if($definition->description)
                                            <p class="text-xs text-[var(--ui-muted)] mt-0.5">{{ $definition->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Answer Content --}}
                            <div class="px-6 py-4">
                                @if($answer !== null)
                                    @if(is_array($answer))
                                        @if(isset($answer['value']))
                                            <p class="text-sm text-[var(--ui-secondary)] leading-relaxed whitespace-pre-wrap">{{ $answer['value'] }}</p>
                                        @else
                                            <div class="space-y-1">
                                                @foreach($answer as $key => $value)
                                                    <div class="flex items-start gap-2">
                                                        <span class="text-xs font-medium text-[var(--ui-muted)] min-w-[80px]">{{ $key }}:</span>
                                                        <span class="text-sm text-[var(--ui-secondary)]">
                                                            @if(is_array($value))
                                                                {{ implode(', ', $value) }}
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-sm text-[var(--ui-secondary)] leading-relaxed whitespace-pre-wrap">{{ $answer }}</p>
                                    @endif
                                @else
                                    <div class="flex items-center gap-2 text-[var(--ui-muted)]">
                                        @svg('heroicon-o-minus-circle', 'w-4 h-4')
                                        <span class="text-sm italic">Keine Antwort</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-50 mb-3">
                        @svg('heroicon-o-document-text', 'w-6 h-6 text-emerald-400')
                    </div>
                    <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Keine Bloecke definiert</p>
                    <p class="text-xs text-[var(--ui-muted)]">Das zugehoerige Board hat keine Bloecke</p>
                </div>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Session-Details" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.intake-boards.show', $session->intakeBoard) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurueck zum Board</span>
                        </a>
                    </div>
                </div>

                {{-- Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Token</span>
                            <code class="text-xs font-medium text-[var(--ui-secondary)]">{{ $session->session_token }}</code>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Status</span>
                            <span class="text-xs font-medium px-2 py-1 rounded-full {{ $session->status === 'completed' ? 'bg-green-50 text-green-600 border border-green-200' : ($session->status === 'in_progress' ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-slate-50 text-slate-600 border border-slate-200') }}">
                                {{ $session->status === 'completed' ? 'Abgeschlossen' : ($session->status === 'in_progress' ? 'In Bearbeitung' : ($session->status ?? 'Unbekannt')) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Schritt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $session->current_step ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Bloecke</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $blocks->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Beantwortet</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ collect($answers)->filter(fn($a) => $a !== null)->count() }} / {{ $blocks->count() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
