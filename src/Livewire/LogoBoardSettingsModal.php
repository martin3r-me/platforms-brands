<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsLogoBoard;
use Livewire\Attributes\On;

class LogoBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $logoBoard;

    #[On('open-modal-logo-board-settings')]
    public function openModal($logoBoardId)
    {
        $this->logoBoard = BrandsLogoBoard::findOrFail($logoBoardId);
        $this->authorize('update', $this->logoBoard);
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'logoBoard.name' => 'required|string|max:255',
            'logoBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        $this->authorize('update', $this->logoBoard);

        $this->logoBoard->save();
        $this->logoBoard->refresh();

        $this->dispatch('updateSidebar');
        $this->dispatch('updateLogoBoard');

        $this->dispatch('notifications:store', [
            'title' => 'Logo Board gespeichert',
            'message' => 'Das Logo Board wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->logoBoard),
            'noticable_id' => $this->logoBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function deleteLogoBoard()
    {
        $this->authorize('delete', $this->logoBoard);

        $brand = $this->logoBoard->brand;
        $this->logoBoard->delete();

        $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.logo-board-settings-modal')->layout('platform::layouts.app');
    }
}
