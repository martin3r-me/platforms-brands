<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsPersonaBoard;
use Livewire\Attributes\On;

class PersonaBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $personaBoardId;
    public $boardName = '';
    public $boardDescription = '';

    #[On('open-modal-persona-board-settings')]
    public function openModal($personaBoardId)
    {
        $this->personaBoardId = $personaBoardId;
        $board = BrandsPersonaBoard::findOrFail($personaBoardId);
        $this->boardName = $board->name;
        $this->boardDescription = $board->description ?? '';
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'boardName' => 'required|string|max:255',
        ];
    }

    public function save()
    {
        $this->validate();

        $board = BrandsPersonaBoard::findOrFail($this->personaBoardId);
        $this->authorize('update', $board);

        $board->update([
            'name' => $this->boardName,
            'description' => $this->boardDescription ?: null,
        ]);

        $this->dispatch('updatePersonaBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.persona-board-settings-modal')->layout('platform::layouts.app');
    }
}
