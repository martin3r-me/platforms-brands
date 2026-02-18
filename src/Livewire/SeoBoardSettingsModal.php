<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsSeoBoard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class SeoBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $seoBoard;

    #[On('open-modal-seo-board-settings')]
    public function openModalSeoBoardSettings($seoBoardId)
    {
        $this->seoBoard = BrandsSeoBoard::findOrFail($seoBoardId);

        $this->authorize('update', $this->seoBoard);

        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'seoBoard.name' => 'required|string|max:255',
            'seoBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();

        $this->authorize('update', $this->seoBoard);

        $this->seoBoard->save();
        $this->seoBoard->refresh();

        $this->dispatch('updateSidebar');
        $this->dispatch('updateSeoBoard');

        $this->dispatch('notifications:store', [
            'title' => 'SEO Board gespeichert',
            'message' => 'Das SEO Board wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->seoBoard),
            'noticable_id' => $this->seoBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function deleteSeoBoard()
    {
        $this->authorize('delete', $this->seoBoard);

        $brand = $this->seoBoard->brand;
        $this->seoBoard->delete();

        $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.seo-board-settings-modal')->layout('platform::layouts.app');
    }
}
