<div>
    <x-ui-page>
        <x-slot name="navbar">
            <x-ui-page-navbar title="Block-Definitionen" icon="heroicon-o-cube">
                <x-slot name="actions">
                    <x-ui-button variant="primary" size="sm" wire:click="openCreate">
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Neue Definition</span>
                        </span>
                    </x-ui-button>
                </x-slot>
            </x-ui-page-navbar>
        </x-slot>

        <x-ui-page-container spacing="space-y-8">
            {{-- Header --}}
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                <div class="p-6 lg:p-8">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-100 to-indigo-50 flex items-center justify-center">
                            @svg('heroicon-o-cube', 'w-6 h-6 text-indigo-600')
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-[var(--ui-secondary)] tracking-tight leading-tight">Block-Definitionen</h1>
                            <p class="text-[var(--ui-muted)] mt-1">Wiederverwendbare Frage-Bausteine fuer Intake-Boards</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Definitions Table --}}
            @if($definitions->count() > 0)
                <div class="bg-white rounded-2xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gradient-to-r from-indigo-50 to-slate-50">
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700 border-b border-indigo-200/60">Name</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700 border-b border-indigo-200/60">Typ</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700 border-b border-indigo-200/60">Beschreibung</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-indigo-700 border-b border-indigo-200/60">Aktiv</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-indigo-700 border-b border-indigo-200/60">Verwendungen</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-indigo-700 border-b border-indigo-200/60">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--ui-border)]/40">
                                @foreach($definitions as $def)
                                    <tr class="hover:bg-[var(--ui-muted-5)] transition-colors group">
                                        <td class="px-6 py-4">
                                            <span class="font-medium text-[var(--ui-secondary)]">{{ $def->name }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                {{ $def->getBlockTypeLabel() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-[var(--ui-muted)] max-w-xs truncate">
                                            {{ Str::limit($def->description, 60) ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if($def->is_active)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100">
                                                    @svg('heroicon-o-check', 'w-4 h-4 text-green-600')
                                                </span>
                                            @else
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
                                                    @svg('heroicon-o-x-mark', 'w-4 h-4 text-gray-400')
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center text-[var(--ui-muted)]">
                                            {{ $def->boardBlocks()->count() }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button wire:click="openEdit({{ $def->id }})" class="p-1.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)] hover:bg-indigo-50 rounded transition-colors" title="Bearbeiten">
                                                    @svg('heroicon-o-pencil', 'w-4 h-4')
                                                </button>
                                                <button wire:click="delete({{ $def->id }})" wire:confirm="Definition wirklich loeschen? Sie wird aus allen Boards entfernt." class="p-1.5 text-[var(--ui-muted)] hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Loeschen">
                                                    @svg('heroicon-o-trash', 'w-4 h-4')
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-16 border-2 border-dashed border-[var(--ui-border)]/40 rounded-xl bg-[var(--ui-muted-5)]">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 mb-4">
                        @svg('heroicon-o-cube', 'w-8 h-8 text-indigo-400')
                    </div>
                    <p class="text-sm font-medium text-[var(--ui-secondary)] mb-1">Noch keine Block-Definitionen</p>
                    <p class="text-xs text-[var(--ui-muted)] mb-4">Erstelle wiederverwendbare Frage-Bausteine fuer deine Intake-Boards</p>
                    <x-ui-button variant="primary" size="sm" wire:click="openCreate">
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Erste Definition erstellen</span>
                        </span>
                    </x-ui-button>
                </div>
            @endif
        </x-ui-page-container>
    </x-ui-page>

    {{-- Modal --}}
    @if($showModal)
        <livewire:brands.intake-block-definition-modal :definitionId="$editingId" :key="'def-modal-'.($editingId ?? 'new')" />
    @endif
</div>
