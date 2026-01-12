<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsBrand;
use Livewire\Attributes\On;

class Brand extends Component
{
    public BrandsBrand $brand;

    public function mount(BrandsBrand $brandsBrand)
    {
        $this->brand = $brandsBrand;
        
        // Berechtigung prüfen
        $this->authorize('view', $this->brand);
    }

    #[On('updateBrand')] 
    public function updateBrand()
    {
        $this->brand->refresh();
    }

    public function createCiBoard()
    {
        $this->authorize('update', $this->brand);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $ciBoard = \Platform\Brands\Models\BrandsCiBoard::create([
            'name' => 'Neues CI Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();
        
        return $this->redirect(route('brands.ci-boards.show', $ciBoard), navigate: true);
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->brand),
            'modelId' => $this->brand->id,
            'subject' => $this->brand->name,
            'description' => $this->brand->description ?? '',
            'url' => route('brands.brands.show', $this->brand),
            'source' => 'brands.brand.view',
            'recipients' => [],
            'capabilities' => [
                'manage_channels' => true,
                'threads' => false,
            ],
            'meta' => [
                'created_at' => $this->brand->created_at,
            ],
        ]);

        // Organization-Kontext setzen - beides erlauben: Zeiten + Entity-Verknüpfung + Dimensionen
        $this->dispatch('organization', [
            'context_type' => get_class($this->brand),
            'context_id' => $this->brand->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
        ]);

        // KeyResult-Kontext setzen - ermöglicht Verknüpfung von KeyResults mit dieser Marke
        $this->dispatch('keyresult', [
            'context_type' => get_class($this->brand),
            'context_id' => $this->brand->id,
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        
        // CI Boards für diese Marke laden
        $boards = $this->brand->ciBoards;

        return view('brands::livewire.brand', [
            'user' => $user,
            'boards' => $boards,
        ])->layout('platform::layouts.app');
    }
}
