<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$intakeBoard->name" icon="heroicon-o-clipboard-document-list">
            <x-slot name="actions">
                <a href="{{ route('brands.brands.show', $intakeBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Zurueck zur Marke</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-8">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-100 to-violet-50 flex items-center justify-center">
                            @svg('heroicon-o-clipboard-document-list', 'w-6 h-6 text-violet-600')
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">{{ $intakeBoard->name }}</h1>
                            @if($intakeBoard->description)
                                <p class="text-[var(--ui-muted)] mt-1">{{ $intakeBoard->description }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <div class="flex items-center gap-3">
                        @if($intakeBoard->isDraft())
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                                @svg('heroicon-o-pencil-square', 'w-3.5 h-3.5')
                                Entwurf
                            </span>
                        @elseif($intakeBoard->isPublished())
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 border border-green-200">
                                @svg('heroicon-o-signal', 'w-3.5 h-3.5')
                                Veroeffentlicht
                            </span>
                        @elseif($intakeBoard->isClosed())
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 border border-red-200">
                                @svg('heroicon-o-lock-closed', 'w-3.5 h-3.5')
                                Geschlossen
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Public URL --}}
                @if($intakeBoard->isPublished() && $intakeBoard->getPublicUrl())
                    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center gap-2 text-sm">
                            @svg('heroicon-o-link', 'w-4 h-4 text-green-600 flex-shrink-0')
                            <span class="text-green-700 font-medium">Oeffentlicher Link:</span>
                            <code class="text-xs bg-white px-2 py-0.5 rounded border border-green-200 text-green-800 select-all">{{ $intakeBoard->getPublicUrl() }}</code>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Block-Liste --}}
        <div>
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-violet-100 to-violet-50 flex items-center justify-center">
                        @svg('heroicon-o-squares-2x2', 'w-5 h-5 text-violet-600')
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Bloecke</h2>
                        <p class="text-sm text-[var(--ui-muted)]">Fragen und Felder der Erhebung</p>
                    </div>
                </div>
                @can('update', $intakeBoard)
                    <x-ui-button variant="primary" size="sm" wire:click="$set('showAddBlockModal', true)">
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Block hinzufuegen</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>

            @if($blocks->count() > 0)
                <div class="space-y-3">
                    @foreach($blocks as $index => $block)
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-4 hover:shadow-md transition-shadow group">
                            <div class="flex items-center gap-4">
                                {{-- Sort Order --}}
                                <div class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center text-sm font-bold text-violet-600 flex-shrink-0">
                                    {{ $index + 1 }}
                                </div>

                                {{-- Block Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-semibold text-[var(--ui-secondary)] truncate">
                                            {{ $block->blockDefinition->name ?? 'Unbekannter Block' }}
                                        </h3>
                                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 flex-shrink-0">
                                            {{ $block->blockDefinition->getBlockTypeLabel() ?? $block->blockDefinition->block_type }}
                                        </span>
                                        @if($block->is_required)
                                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200 flex-shrink-0">
                                                Pflicht
                                            </span>
                                        @endif
                                    </div>
                                    @if($block->blockDefinition->description)
                                        <p class="text-xs text-[var(--ui-muted)] mt-0.5 truncate">{{ $block->blockDefinition->description }}</p>
                                    @endif
                                </div>

                                {{-- Actions --}}
                                @can('update', $intakeBoard)
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                                        <button wire:click="moveBlock({{ $block->id }}, 'up')" class="p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-violet-50 rounded transition-colors" title="Nach oben">
                                            @svg('heroicon-o-chevron-up', 'w-4 h-4')
                                        </button>
                                        <button wire:click="moveBlock({{ $block->id }}, 'down')" class="p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-violet-50 rounded transition-colors" title="Nach unten">
                                            @svg('heroicon-o-chevron-down', 'w-4 h-4')
                                        </button>
                                        <button wire:click="removeBlock({{ $block->id }})" wire:confirm="Block wirklich entfernen?" class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Entfernen">
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                        </button>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-50 mb-4">
                        @svg('heroicon-o-squares-2x2', 'w-8 h-8 text-violet-400')
                    </div>
                    <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Bloecke</p>
                    <p class="text-xs text-[var(--ui-muted)] mb-4">Fuege Bloecke hinzu, um die Erhebung zu strukturieren</p>
                    @can('update', $intakeBoard)
                        <x-ui-button variant="primary" size="sm" wire:click="$set('showAddBlockModal', true)">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Block hinzufuegen</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
            @endif
        </div>

        {{-- Sessions --}}
        <div>
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                    @svg('heroicon-o-chat-bubble-left-right', 'w-5 h-5 text-blue-600')
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-[var(--ui-secondary)]">Sessions</h2>
                    <p class="text-sm text-[var(--ui-muted)]">Eingegangene Antworten und Befragungen</p>
                </div>
            </div>

            @if($sessions->count() > 0)
                <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gradient-to-r from-blue-50 to-slate-50">
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-blue-700 border-b border-blue-200/60">Token</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-blue-700 border-b border-blue-200/60">Name</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-blue-700 border-b border-blue-200/60">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-blue-700 border-b border-blue-200/60">Gestartet</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-blue-700 border-b border-blue-200/60">Abgeschlossen</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-blue-700 border-b border-blue-200/60"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--ui-border)]/40">
                                @foreach($sessions as $session)
                                    <tr class="hover:bg-[var(--ui-muted-5)] transition-colors">
                                        <td class="px-6 py-3">
                                            <code class="text-xs bg-slate-100 px-2 py-0.5 rounded border border-slate-200 text-slate-700">{{ $session->session_token }}</code>
                                        </td>
                                        <td class="px-6 py-3 text-[var(--ui-secondary)]">
                                            {{ $session->respondent_name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-3">
                                            @if($session->status === 'completed')
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">
                                                    @svg('heroicon-o-check-circle', 'w-3 h-3')
                                                    Abgeschlossen
                                                </span>
                                            @elseif($session->status === 'in_progress')
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                                    @svg('heroicon-o-clock', 'w-3 h-3')
                                                    In Bearbeitung
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 bg-slate-50 px-2 py-0.5 rounded-full border border-slate-200">
                                                    {{ $session->status ?? 'Unbekannt' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-xs text-[var(--ui-muted)]">
                                            {{ $session->started_at?->format('d.m.Y H:i') ?? '-' }}
                                        </td>
                                        <td class="px-6 py-3 text-xs text-[var(--ui-muted)]">
                                            {{ $session->completed_at?->format('d.m.Y H:i') ?? '-' }}
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <a href="{{ route('brands.intake-sessions.show', $session) }}" class="inline-flex items-center gap-1 text-xs font-medium text-[var(--ui-primary)] hover:underline">
                                                Ansehen
                                                @svg('heroicon-o-arrow-right', 'w-3 h-3')
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-12 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 mb-3">
                        @svg('heroicon-o-chat-bubble-left-right', 'w-6 h-6 text-blue-400')
                    </div>
                    <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Sessions</p>
                    <p class="text-xs text-[var(--ui-muted)]">Sessions werden erstellt, wenn Teilnehmer die Erhebung starten</p>
                </div>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board-Uebersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('brands.brands.show', $intakeBoard->brand) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Zurueck zur Marke</span>
                        </a>
                    </div>
                </div>

                {{-- Status-Aktionen --}}
                @can('update', $intakeBoard)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                        <div class="flex flex-col gap-2">
                            @if($intakeBoard->isDraft())
                                <x-ui-button variant="primary" size="sm" wire:click="publish" wire:confirm="Erhebung jetzt veroeffentlichen? Sie wird oeffentlich zugaenglich." class="w-full">
                                    <span class="inline-flex items-center gap-2">
                                        @svg('heroicon-o-signal', 'w-4 h-4')
                                        <span>Veroeffentlichen</span>
                                    </span>
                                </x-ui-button>
                            @elseif($intakeBoard->isPublished())
                                <x-ui-button variant="secondary-outline" size="sm" wire:click="close" wire:confirm="Erhebung schliessen? Es koennen keine neuen Antworten mehr eingehen." class="w-full">
                                    <span class="inline-flex items-center gap-2">
                                        @svg('heroicon-o-lock-closed', 'w-4 h-4')
                                        <span>Schliessen</span>
                                    </span>
                                </x-ui-button>
                                <x-ui-button variant="secondary-outline" size="sm" wire:click="unpublish" wire:confirm="Zurueck in den Entwurf setzen?" class="w-full">
                                    <span class="inline-flex items-center gap-2">
                                        @svg('heroicon-o-pencil-square', 'w-4 h-4')
                                        <span>Zurueck zu Entwurf</span>
                                    </span>
                                </x-ui-button>
                            @elseif($intakeBoard->isClosed())
                                <x-ui-button variant="secondary-outline" size="sm" wire:click="unpublish" wire:confirm="Zurueck in den Entwurf setzen?" class="w-full">
                                    <span class="inline-flex items-center gap-2">
                                        @svg('heroicon-o-pencil-square', 'w-4 h-4')
                                        <span>Wieder oeffnen (Entwurf)</span>
                                    </span>
                                </x-ui-button>
                            @endif

                            <x-ui-button variant="secondary-outline" size="sm" wire:click="$set('showSettingsModal', true)" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                    <span>Einstellungen</span>
                                </span>
                            </x-ui-button>
                        </div>
                    </div>
                @endcan

                {{-- Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Status</span>
                            <span class="text-xs font-medium px-2 py-1 rounded-full {{ $intakeBoard->isDraft() ? 'bg-gray-50 text-gray-600 border border-gray-200' : ($intakeBoard->isPublished() ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200') }}">
                                {{ \Platform\Brands\Models\BrandsIntakeBoard::STATUSES[$intakeBoard->status] ?? $intakeBoard->status }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Bloecke</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $blocks->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Sessions</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $sessions->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Erstellt</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $intakeBoard->created_at->format('d.m.Y') }}</span>
                        </div>
                        @if($intakeBoard->started_at)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Gestartet</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $intakeBoard->started_at->format('d.m.Y') }}</span>
                            </div>
                        @endif
                        @if($intakeBoard->completed_at)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                                <span class="text-sm text-[var(--ui-muted)]">Geschlossen</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">{{ $intakeBoard->completed_at->format('d.m.Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Add Block Modal --}}
    @if($showAddBlockModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="$set('showAddBlockModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="addBlock">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-violet-100 to-violet-50 flex items-center justify-center">
                                    @svg('heroicon-o-plus-circle', 'w-5 h-5 text-violet-600')
                                </div>
                                <h3 class="text-lg font-bold text-[var(--ui-secondary)]">Block hinzufuegen</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label for="selectedDefinitionId" class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">Block-Definition *</label>
                                    <select id="selectedDefinitionId" wire:model="selectedDefinitionId" class="w-full rounded-lg border border-[var(--ui-border)] px-3 py-2 text-sm focus:border-violet-500 focus:ring-violet-500">
                                        <option value="">-- Bitte waehlen --</option>
                                        @foreach($availableDefinitions as $def)
                                            <option value="{{ $def->id }}">{{ $def->name }} ({{ $def->getBlockTypeLabel() }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="isRequired" class="rounded border-[var(--ui-border)] text-violet-600 focus:ring-violet-500">
                                        <span class="text-sm font-medium text-[var(--ui-secondary)]">Pflichtfeld</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-[var(--ui-muted-5)] px-6 py-4 flex items-center justify-end gap-3 border-t border-[var(--ui-border)]/40">
                            <x-ui-button variant="secondary-outline" size="sm" type="button" wire:click="$set('showAddBlockModal', false)">
                                Abbrechen
                            </x-ui-button>
                            <x-ui-button variant="primary" size="sm" type="submit">
                                Hinzufuegen
                            </x-ui-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Settings Modal --}}
    @if($showSettingsModal)
        <livewire:brands.intake-board-settings-modal :intakeBoard="$intakeBoard" :key="'settings-'.$intakeBoard->id" />
    @endif
</x-ui-page>
