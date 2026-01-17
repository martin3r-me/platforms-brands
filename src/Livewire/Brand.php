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


    /**
     * Facebook Page mit Brand verknüpfen
     */
    public function attachFacebookPage($facebookPageId)
    {
        $this->authorize('update', $this->brand);
        
        $user = Auth::user();
        $facebookPage = \Platform\Brands\Models\FacebookPage::where('id', $facebookPageId)
            ->where('user_id', $user->id)
            ->where('team_id', $user->currentTeam?->id)
            ->firstOrFail();
        
        // Prüfen ob bereits verknüpft
        if (!$this->brand->facebookPages()->where('facebook_pages.id', $facebookPageId)->exists()) {
            $this->brand->facebookPages()->attach($facebookPageId);
            $this->brand->refresh();
            
            session()->flash('success', 'Facebook Page wurde erfolgreich mit der Marke verknüpft.');
        } else {
            session()->flash('info', 'Facebook Page ist bereits mit dieser Marke verknüpft.');
        }
    }

    /**
     * Facebook Page von Brand trennen
     */
    public function detachFacebookPage($facebookPageId)
    {
        $this->authorize('update', $this->brand);
        
        $this->brand->facebookPages()->detach($facebookPageId);
        $this->brand->refresh();
        
        session()->flash('success', 'Facebook Page wurde erfolgreich von der Marke getrennt.');
    }

    /**
     * Instagram Account mit Brand verknüpfen
     */
    public function attachInstagramAccount($instagramAccountId)
    {
        $this->authorize('update', $this->brand);
        
        $user = Auth::user();
        $instagramAccount = \Platform\Brands\Models\InstagramAccount::where('id', $instagramAccountId)
            ->where('user_id', $user->id)
            ->where('team_id', $user->currentTeam?->id)
            ->firstOrFail();
        
        // Prüfen ob bereits verknüpft
        if (!$this->brand->instagramAccounts()->where('instagram_accounts.id', $instagramAccountId)->exists()) {
            $this->brand->instagramAccounts()->attach($instagramAccountId);
            $this->brand->refresh();
            
            session()->flash('success', 'Instagram Account wurde erfolgreich mit der Marke verknüpft.');
        } else {
            session()->flash('info', 'Instagram Account ist bereits mit dieser Marke verknüpft.');
        }
    }

    /**
     * Instagram Account von Brand trennen
     */
    public function detachInstagramAccount($instagramAccountId)
    {
        $this->authorize('update', $this->brand);
        
        $this->brand->instagramAccounts()->detach($instagramAccountId);
        $this->brand->refresh();
        
        session()->flash('success', 'Instagram Account wurde erfolgreich von der Marke getrennt.');
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        
        // CI Boards und Content Boards für diese Marke laden
        $ciBoards = $this->brand->ciBoards;
        $contentBoards = $this->brand->contentBoards;
        
        // Facebook Pages und Instagram Accounts für diese Marke laden
        $facebookPages = $this->brand->facebookPages;
        $instagramAccounts = $this->brand->instagramAccounts;
        
        // Meta Token laden
        $metaToken = $this->brand->metaToken;

        // Verfügbare Facebook Pages und Instagram Accounts des Users (noch nicht verknüpft)
        $availableFacebookPages = collect();
        $availableInstagramAccounts = collect();
        
        if ($team && $metaToken) {
            // Alle Facebook Pages des Users im aktuellen Team
            $allUserFacebookPages = \Platform\Brands\Models\FacebookPage::where('user_id', $user->id)
                ->where('team_id', $team->id)
                ->get();
            
            // Nur die, die noch nicht mit dieser Brand verknüpft sind
            $availableFacebookPages = $allUserFacebookPages->reject(function ($page) use ($facebookPages) {
                return $facebookPages->contains('id', $page->id);
            });
            
            // Alle Instagram Accounts des Users im aktuellen Team
            $allUserInstagramAccounts = \Platform\Brands\Models\InstagramAccount::where('user_id', $user->id)
                ->where('team_id', $team->id)
                ->get();
            
            // Nur die, die noch nicht mit dieser Brand verknüpft sind
            $availableInstagramAccounts = $allUserInstagramAccounts->reject(function ($account) use ($instagramAccounts) {
                return $instagramAccounts->contains('id', $account->id);
            });
        }

        return view('brands::livewire.brand', [
            'user' => $user,
            'ciBoards' => $ciBoards,
            'contentBoards' => $contentBoards,
            'facebookPages' => $facebookPages,
            'instagramAccounts' => $instagramAccounts,
            'availableFacebookPages' => $availableFacebookPages,
            'availableInstagramAccounts' => $availableInstagramAccounts,
            'metaToken' => $metaToken,
        ])->layout('platform::layouts.app');
    }
}
