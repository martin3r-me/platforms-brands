<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class SeoBoardInfoModal extends Component
{
    public $modalShow = false;

    #[On('open-modal-seo-board-info')]
    public function openModalSeoBoardInfo()
    {
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.seo-board-info-modal')
            ->layout('platform::layouts.app');
    }
}
