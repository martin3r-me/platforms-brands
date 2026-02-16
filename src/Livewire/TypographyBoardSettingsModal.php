<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsTypographyBoard;
use Livewire\Attributes\On;

class TypographyBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $typographyBoard;

    #[On('open-modal-typography-board-settings')]
    public function openModal($typographyBoardId)
    {
        $this->typographyBoard = BrandsTypographyBoard::findOrFail($typographyBoardId);
        $this->authorize('update', $this->typographyBoard);
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'typographyBoard.name' => 'required|string|max:255',
            'typographyBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        $this->authorize('update', $this->typographyBoard);

        $this->typographyBoard->save();
        $this->typographyBoard->refresh();

        $this->dispatch('updateSidebar');
        $this->dispatch('updateTypographyBoard');

        $this->dispatch('notifications:store', [
            'title' => 'Typografie Board gespeichert',
            'message' => 'Das Typografie Board wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->typographyBoard),
            'noticable_id' => $this->typographyBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function deleteTypographyBoard()
    {
        $this->authorize('delete', $this->typographyBoard);

        $brand = $this->typographyBoard->brand;
        $this->typographyBoard->delete();

        $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.typography-board-settings-modal')->layout('platform::layouts.app');
    }
}
