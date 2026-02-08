<x-ui-modal size="md" model="modalShow" header="Kanban Board-Einstellungen">
    @if($kanbanBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Kanban Board Name --}}
            @can('update', $kanbanBoard)
                <x-ui-input-text
                    name="kanbanBoard.name"
                    label="Kanban Board Name"
                    wire:model.live.debounce.500ms="kanbanBoard.name"
                    placeholder="Kanban Board Name eingeben..."
                    required
                    :errorKey="'kanbanBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">Kanban Board Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $kanbanBoard->name }}</span>
                </div>
            @endcan

            {{-- Beschreibung --}}
            @can('update', $kanbanBoard)
                <x-ui-input-textarea
                    name="kanbanBoard.description"
                    label="Beschreibung"
                    wire:model.live.debounce.500ms="kanbanBoard.description"
                    placeholder="Beschreibung des Kanban Boards eingeben..."
                    :errorKey="'kanbanBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Beschreibung</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $kanbanBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>

        {{-- Kanban Board löschen --}}
        @can('delete', $kanbanBoard)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteKanbanBoard" text="Kanban Board löschen" confirmText="Wirklich löschen?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($kanbanBoard)
            @can('update', $kanbanBoard)
                <x-ui-button variant="success" wire:click="save">Speichern</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
