<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsContentBoard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class ContentBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $contentBoard;

    #[On('open-modal-content-board-settings')] 
    public function openModalContentBoardSettings($contentBoardId)
    {
        $this->contentBoard = BrandsContentBoard::findOrFail($contentBoardId);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->contentBoard);
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'contentBoard.name' => 'required|string|max:255',
            'contentBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->contentBoard);

        $this->contentBoard->save();
        $this->contentBoard->refresh();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updateContentBoard');

        $this->dispatch('notifications:store', [
            'title' => 'Content Board gespeichert',
            'message' => 'Das Content Board wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->contentBoard),
            'noticable_id'   => $this->contentBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function deleteContentBoard()
    {
        // Policy-Berechtigung prüfen
        $this->authorize('delete', $this->contentBoard);
        
        $brand = $this->contentBoard->brand;
        $this->contentBoard->delete();
        
        // Zurück zur Marke leiten
        $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.content-board-settings-modal')->layout('platform::layouts.app');
    }
}
