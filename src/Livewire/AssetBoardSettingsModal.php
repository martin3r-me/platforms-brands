<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsAssetBoard;
use Livewire\Attributes\On;

class AssetBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $board;

    public $boardName = '';
    public $boardDescription = '';

    #[On('open-modal-asset-board-settings')]
    public function openModal($assetBoardId)
    {
        $this->board = BrandsAssetBoard::findOrFail($assetBoardId);
        $this->boardName = $this->board->name;
        $this->boardDescription = $this->board->description ?? '';
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
        $this->authorize('update', $this->board);

        $this->board->update([
            'name' => $this->boardName,
            'description' => $this->boardDescription ?: null,
        ]);

        $this->dispatch('updateAssetBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.asset-board-settings-modal')->layout('platform::layouts.app');
    }
}
