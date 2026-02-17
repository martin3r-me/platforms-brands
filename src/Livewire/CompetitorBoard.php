<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Platform\Brands\Models\BrandsCompetitor;
use Livewire\Attributes\On;

class CompetitorBoard extends Component
{
    public BrandsCompetitorBoard $competitorBoard;

    public function mount(BrandsCompetitorBoard $brandsCompetitorBoard)
    {
        $this->competitorBoard = $brandsCompetitorBoard->fresh()->load(['competitors']);
        $this->authorize('view', $this->competitorBoard);
    }

    #[On('updateCompetitorBoard')]
    public function updateCompetitorBoard()
    {
        $this->competitorBoard->refresh();
        $this->competitorBoard->load(['competitors']);
    }

    public function createCompetitor()
    {
        $this->authorize('update', $this->competitorBoard);

        BrandsCompetitor::create([
            'competitor_board_id' => $this->competitorBoard->id,
            'name' => 'Neuer Wettbewerber',
        ]);

        $this->competitorBoard->refresh();
        $this->competitorBoard->load(['competitors']);
    }

    public function deleteCompetitor($competitorId)
    {
        $this->authorize('update', $this->competitorBoard);

        $competitor = BrandsCompetitor::findOrFail($competitorId);
        $competitor->delete();

        $this->competitorBoard->refresh();
        $this->competitorBoard->load(['competitors']);
    }

    public function updateCompetitorPosition($competitorId, $positionX, $positionY)
    {
        $this->authorize('update', $this->competitorBoard);

        $competitor = BrandsCompetitor::findOrFail($competitorId);
        $competitor->update([
            'position_x' => max(0, min(100, intval($positionX))),
            'position_y' => max(0, min(100, intval($positionY))),
        ]);

        $this->competitorBoard->refresh();
        $this->competitorBoard->load(['competitors']);
    }

    public function updateCompetitorOrder($groups)
    {
        $this->authorize('update', $this->competitorBoard);

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $competitor = BrandsCompetitor::find($item['value']);
                if ($competitor) {
                    $competitor->order = $item['order'];
                    $competitor->save();
                }
            }
        }

        $this->competitorBoard->refresh();
        $this->competitorBoard->load(['competitors']);
    }

    public function render()
    {
        $user = Auth::user();
        $competitors = $this->competitorBoard->competitors()->orderBy('order')->get();
        $ownBrand = $competitors->where('is_own_brand', true)->first();

        return view('brands::livewire.competitor-board', [
            'user' => $user,
            'competitors' => $competitors,
            'ownBrand' => $ownBrand,
        ])->layout('platform::layouts.app');
    }
}
