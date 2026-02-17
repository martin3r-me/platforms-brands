<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Livewire\Attributes\On;

class CompetitorBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $competitorBoardId;
    public $boardName = '';
    public $boardDescription = '';
    public $axisXLabel = 'Preis';
    public $axisYLabel = 'Qualität';
    public $axisXMinLabel = 'Niedrig';
    public $axisXMaxLabel = 'Hoch';
    public $axisYMinLabel = 'Niedrig';
    public $axisYMaxLabel = 'Hoch';

    #[On('open-modal-competitor-board-settings')]
    public function openModal($competitorBoardId)
    {
        $this->competitorBoardId = $competitorBoardId;
        $board = BrandsCompetitorBoard::findOrFail($competitorBoardId);
        $this->boardName = $board->name;
        $this->boardDescription = $board->description ?? '';
        $this->axisXLabel = $board->axis_x_label ?? 'Preis';
        $this->axisYLabel = $board->axis_y_label ?? 'Qualität';
        $this->axisXMinLabel = $board->axis_x_min_label ?? 'Niedrig';
        $this->axisXMaxLabel = $board->axis_x_max_label ?? 'Hoch';
        $this->axisYMinLabel = $board->axis_y_min_label ?? 'Niedrig';
        $this->axisYMaxLabel = $board->axis_y_max_label ?? 'Hoch';
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
            'axisXLabel' => 'required|string|max:100',
            'axisYLabel' => 'required|string|max:100',
        ];
    }

    public function save()
    {
        $this->validate();

        $board = BrandsCompetitorBoard::findOrFail($this->competitorBoardId);
        $this->authorize('update', $board);

        $board->update([
            'name' => $this->boardName,
            'description' => $this->boardDescription ?: null,
            'axis_x_label' => $this->axisXLabel,
            'axis_y_label' => $this->axisYLabel,
            'axis_x_min_label' => $this->axisXMinLabel,
            'axis_x_max_label' => $this->axisXMaxLabel,
            'axis_y_min_label' => $this->axisYMinLabel,
            'axis_y_max_label' => $this->axisYMaxLabel,
        ]);

        $this->dispatch('updateCompetitorBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.competitor-board-settings-modal')->layout('platform::layouts.app');
    }
}
