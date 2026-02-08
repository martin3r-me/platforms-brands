<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsKanbanBoardSlot;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class KanbanBoardSlotSettingsModal extends Component
{
    public $modalShow = false;
    public $slot;

    #[On('open-modal-kanban-board-slot-settings')]
    public function openModalKanbanBoardSlotSettings(...$args)
    {
        // Payload kann als ID kommen oder als Array/Objekt { slotId: X }
        $payload = $args[0] ?? null;
        $id = is_array($payload)
            ? ($payload['slotId'] ?? $payload['id'] ?? null)
            : (is_object($payload) ? ($payload->slotId ?? $payload->id ?? null) : $payload);

        if (!$id) {
            return; // kein valides Payload, still ignorieren
        }

        $this->slot = BrandsKanbanBoardSlot::findOrFail($id);

        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->slot->kanbanBoard);

        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'slot.name' => 'required|string|max:255',
        ];
    }

    public function save()
    {
        if (!$this->slot) {
            return;
        }

        $this->validate();

        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->slot->kanbanBoard);

        $this->slot->save();

        $this->dispatch('updateKanbanBoard');
        $this->closeModal();
    }

    public function deleteSlot()
    {
        if (!$this->slot) {
            return;
        }

        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->slot->kanbanBoard);

        $this->slot->delete();

        $this->dispatch('updateKanbanBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.kanban-board-slot-settings-modal')->layout('platform::layouts.app');
    }
}
