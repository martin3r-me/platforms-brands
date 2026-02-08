<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsKanbanBoard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class KanbanBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $kanbanBoard;

    #[On('open-modal-kanban-board-settings')]
    public function openModalKanbanBoardSettings($kanbanBoardId)
    {
        $this->kanbanBoard = BrandsKanbanBoard::findOrFail($kanbanBoardId);

        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->kanbanBoard);

        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'kanbanBoard.name' => 'required|string|max:255',
            'kanbanBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();

        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->kanbanBoard);

        $this->kanbanBoard->save();
        $this->kanbanBoard->refresh();

        $this->dispatch('updateSidebar');
        $this->dispatch('updateKanbanBoard');

        $this->dispatch('notifications:store', [
            'title' => 'Kanban Board gespeichert',
            'message' => 'Das Kanban Board wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->kanbanBoard),
            'noticable_id'   => $this->kanbanBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function deleteKanbanBoard()
    {
        // Policy-Berechtigung prüfen
        $this->authorize('delete', $this->kanbanBoard);

        $brand = $this->kanbanBoard->brand;
        $this->kanbanBoard->delete();

        // Zurück zur Marke leiten
        $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.kanban-board-settings-modal')->layout('platform::layouts.app');
    }
}
