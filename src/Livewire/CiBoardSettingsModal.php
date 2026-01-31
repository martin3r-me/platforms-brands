<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsCiBoard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class CiBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $ciBoard;

    #[On('open-modal-ci-board-settings')] 
    public function openModalCiBoardSettings($ciBoardId)
    {
        $this->ciBoard = BrandsCiBoard::findOrFail($ciBoardId);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->ciBoard);
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'ciBoard.name' => 'required|string|max:255',
            'ciBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->ciBoard);

        $this->ciBoard->save();
        $this->ciBoard->refresh();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updateCiBoard');

        $this->dispatch('notifications:store', [
            'title' => 'CI Board gespeichert',
            'message' => 'Das CI Board wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->ciBoard),
            'noticable_id'   => $this->ciBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function deleteCiBoard()
    {
        // Policy-Berechtigung prüfen
        $this->authorize('delete', $this->ciBoard);
        
        $brand = $this->ciBoard->brand;
        $this->ciBoard->delete();
        
        // Zurück zur Marke leiten
        $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.ci-board-settings-modal')->layout('platform::layouts.app');
    }
}
