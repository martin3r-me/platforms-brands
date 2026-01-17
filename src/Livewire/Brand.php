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

    public function createContentBoard()
    {
        $this->authorize('update', $this->brand);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $contentBoard = \Platform\Brands\Models\BrandsContentBoard::create([
            'name' => 'Neues Content Board',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->brand->id,
        ]);

        $this->brand->refresh();
        
        return $this->redirect(route('brands.content-boards.show', $contentBoard), navigate: true);
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

    public function deleteMetaToken()
    {
        $this->authorize('update', $this->brand);
        
        $metaToken = $this->brand->metaToken;
        
        if ($metaToken) {
            $metaToken->delete();
            $this->brand->refresh();
            
            session()->flash('success', 'Meta-Verknüpfung wurde erfolgreich entfernt.');
        }
    }

    public function render()
    {
        $user = Auth::user();
        
        // CI Boards und Content Boards für diese Marke laden
        $ciBoards = $this->brand->ciBoards;
        $contentBoards = $this->brand->contentBoards;
        
        // Facebook Pages und Instagram Accounts für diese Marke laden
        $facebookPages = $this->brand->facebookPages;
        $instagramAccounts = $this->brand->instagramAccounts;
        
        // Meta Token laden
        $metaToken = $this->brand->metaToken;

        return view('brands::livewire.brand', [
            'user' => $user,
            'ciBoards' => $ciBoards,
            'contentBoards' => $contentBoards,
            'facebookPages' => $facebookPages,
            'instagramAccounts' => $instagramAccounts,
            'metaToken' => $metaToken,
        ])->layout('platform::layouts.app');
    }
}
