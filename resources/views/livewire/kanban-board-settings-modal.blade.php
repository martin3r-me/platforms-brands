<x-nx-modal size="md" model="modalShow" header="Kanban Board-Einstellungen">
    @if($kanbanBoard)
        <div class="space-y-4">
            {{-- Kanban Board Name --}}
            @can('update', $kanbanBoard)
                <x-nx-input-text
                    name="kanbanBoard.name"
                    label="Kanban Board Name"
                    wire:model.live.debounce.500ms="kanbanBoard.name"
                    placeholder="Kanban Board Name eingeben..."
                    required
                    :errorKey="'kanbanBoard.name'"
                />
            @else
                <div class="flex items-center justify-between rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Kanban Board Name</span>
                    <span class="font-medium text-[color:var(--nx-text)]">{{ $kanbanBoard->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $kanbanBoard)
                <x-nx-input-textarea
                    name="kanbanBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="kanbanBoard.description"
                    placeholder="Beschreibung des Kanban Boards eingeben..."
                    :errorKey="'kanbanBoard.description'"
                />
            @else
                <div class="flex items-start justify-between gap-3 rounded-[6px] border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)] px-3 py-2 text-sm">
                    <span class="text-[color:var(--nx-faint)]">Beschreibung</span>
                    <span class="text-right font-medium text-[color:var(--nx-text)]">{{ $kanbanBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </div>

        {{-- Kanban Board löschen --}}
        @can('delete', $kanbanBoard)
            <div class="mt-4">
                <x-nx-button variant="danger" size="sm" wire:click="deleteKanbanBoard" wire:confirm="Wirklich löschen?">
                    @svg('heroicon-o-trash', 'w-4 h-4') Kanban Board löschen
                </x-nx-button>
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($kanbanBoard)
            @can('update', $kanbanBoard)
                <x-nx-button variant="primary" wire:click="save">Speichern</x-nx-button>
            @endcan
        @endif
    </x-slot>
</x-nx-modal>
