<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsFacebookPage;
use Livewire\Attributes\On;

class FacebookPage extends Component
{
    public BrandsFacebookPage $facebookPage;

    public function mount(BrandsFacebookPage $brandsFacebookPage)
    {
        $this->facebookPage = $brandsFacebookPage->fresh();
        
        // Berechtigung prüfen
        $this->authorize('view', $this->facebookPage);
    }

    #[On('updateFacebookPage')] 
    public function updateFacebookPage()
    {
        $this->facebookPage->refresh();
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.facebook-page', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
