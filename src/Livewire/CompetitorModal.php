<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Platform\Brands\Models\BrandsCompetitor;
use Livewire\Attributes\On;

class CompetitorModal extends Component
{
    public $modalShow = false;
    public $competitor;
    public $competitorBoardId;

    // Competitor fields
    public $competitorName = '';
    public $competitorLogoUrl = '';
    public $competitorWebsiteUrl = '';
    public $competitorDescription = '';
    public $competitorStrengths = [];
    public $competitorWeaknesses = [];
    public $competitorNotes = '';
    public $competitorPositionX = 50;
    public $competitorPositionY = 50;
    public $competitorIsOwnBrand = false;
    public $competitorDifferentiation = [];

    #[On('open-modal-competitor')]
    public function openModal($competitorBoardId, $competitorId = null)
    {
        $this->competitorBoardId = $competitorBoardId;
        $this->resetFields();

        if ($competitorId) {
            $this->competitor = BrandsCompetitor::findOrFail($competitorId);
            $this->fillFromCompetitor();
        } else {
            $this->competitor = null;
        }

        $this->modalShow = true;
    }

    protected function resetFields()
    {
        $this->competitorName = '';
        $this->competitorLogoUrl = '';
        $this->competitorWebsiteUrl = '';
        $this->competitorDescription = '';
        $this->competitorStrengths = [];
        $this->competitorWeaknesses = [];
        $this->competitorNotes = '';
        $this->competitorPositionX = 50;
        $this->competitorPositionY = 50;
        $this->competitorIsOwnBrand = false;
        $this->competitorDifferentiation = [];
    }

    protected function fillFromCompetitor()
    {
        $this->competitorName = $this->competitor->name;
        $this->competitorLogoUrl = $this->competitor->logo_url ?? '';
        $this->competitorWebsiteUrl = $this->competitor->website_url ?? '';
        $this->competitorDescription = $this->competitor->description ?? '';
        $this->competitorStrengths = $this->competitor->strengths ?? [];
        $this->competitorWeaknesses = $this->competitor->weaknesses ?? [];
        $this->competitorNotes = $this->competitor->notes ?? '';
        $this->competitorPositionX = $this->competitor->position_x ?? 50;
        $this->competitorPositionY = $this->competitor->position_y ?? 50;
        $this->competitorIsOwnBrand = $this->competitor->is_own_brand ?? false;
        $this->competitorDifferentiation = $this->competitor->differentiation ?? [];
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'competitorName' => 'required|string|max:255',
        ];
    }

    // Dynamic list management methods
    public function addStrength()
    {
        $this->competitorStrengths[] = ['text' => ''];
    }

    public function removeStrength($index)
    {
        unset($this->competitorStrengths[$index]);
        $this->competitorStrengths = array_values($this->competitorStrengths);
    }

    public function addWeakness()
    {
        $this->competitorWeaknesses[] = ['text' => ''];
    }

    public function removeWeakness($index)
    {
        unset($this->competitorWeaknesses[$index]);
        $this->competitorWeaknesses = array_values($this->competitorWeaknesses);
    }

    public function addDifferentiation()
    {
        $this->competitorDifferentiation[] = ['category' => '', 'own_value' => '', 'competitor_value' => ''];
    }

    public function removeDifferentiation($index)
    {
        unset($this->competitorDifferentiation[$index]);
        $this->competitorDifferentiation = array_values($this->competitorDifferentiation);
    }

    public function save()
    {
        $this->validate();

        $board = BrandsCompetitorBoard::findOrFail($this->competitorBoardId);
        $this->authorize('update', $board);

        // Filter empty entries from arrays
        $filterEmpty = fn($arr) => array_values(array_filter($arr, fn($item) => !empty($item['text'])));
        $filterEmptyDiff = fn($arr) => array_values(array_filter($arr, fn($item) => !empty($item['category'])));

        $data = [
            'name' => $this->competitorName,
            'logo_url' => $this->competitorLogoUrl ?: null,
            'website_url' => $this->competitorWebsiteUrl ?: null,
            'description' => $this->competitorDescription ?: null,
            'strengths' => $filterEmpty($this->competitorStrengths) ?: null,
            'weaknesses' => $filterEmpty($this->competitorWeaknesses) ?: null,
            'notes' => $this->competitorNotes ?: null,
            'position_x' => $this->competitorPositionX,
            'position_y' => $this->competitorPositionY,
            'is_own_brand' => $this->competitorIsOwnBrand,
            'differentiation' => $filterEmptyDiff($this->competitorDifferentiation) ?: null,
        ];

        if ($this->competitor) {
            $this->competitor->update($data);
        } else {
            $data['competitor_board_id'] = $this->competitorBoardId;
            BrandsCompetitor::create($data);
        }

        $this->dispatch('updateCompetitorBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.competitor-modal')->layout('platform::layouts.app');
    }
}
