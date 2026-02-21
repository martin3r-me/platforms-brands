<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsCtaBoard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class CtaBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $ctaBoard;

    #[On('open-modal-cta-board-settings')]
    public function openModalCtaBoardSettings($ctaBoardId)
    {
        $this->ctaBoard = BrandsCtaBoard::findOrFail($ctaBoardId);

        $this->authorize('update', $this->ctaBoard);

        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'ctaBoard.name' => 'required|string|max:255',
            'ctaBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();

        $this->authorize('update', $this->ctaBoard);

        $this->ctaBoard->save();
        $this->ctaBoard->refresh();

        $this->dispatch('updateSidebar');
        $this->dispatch('updateCtaBoard');

        $this->dispatch('notifications:store', [
            'title' => 'CTA Board gespeichert',
            'message' => 'Das CTA Board wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->ctaBoard),
            'noticable_id' => $this->ctaBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function deleteCtaBoard()
    {
        $this->authorize('delete', $this->ctaBoard);

        $brand = $this->ctaBoard->brand;
        $this->ctaBoard->delete();

        $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.cta-board-settings-modal')->layout('platform::layouts.app');
    }
}
