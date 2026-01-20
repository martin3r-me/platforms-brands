<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsSocialBoard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class SocialBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $socialBoard;

    #[On('open-modal-social-board-settings')] 
    public function openModalSocialBoardSettings($socialBoardId)
    {
        $this->socialBoard = BrandsSocialBoard::findOrFail($socialBoardId);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->socialBoard);
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'socialBoard.name' => 'required|string|max:255',
            'socialBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->socialBoard);

        $this->socialBoard->save();
        $this->socialBoard->refresh();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updateSocialBoard');

        $this->dispatch('notifications:store', [
            'title' => 'Social Board gespeichert',
            'message' => 'Das Social Board wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->socialBoard),
            'noticable_id'   => $this->socialBoard->getKey(),
        ]);

        $this->reset('socialBoard');
        $this->closeModal();
    }

    public function deleteSocialBoard()
    {
        // Policy-Berechtigung prüfen
        $this->authorize('delete', $this->socialBoard);
        
        $brand = $this->socialBoard->brand;
        $this->socialBoard->delete();
        
        // Zurück zur Marke leiten
        $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.social-board-settings-modal')->layout('platform::layouts.app');
    }
}
