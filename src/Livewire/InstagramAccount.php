<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsInstagramAccount;
use Livewire\Attributes\On;

class InstagramAccount extends Component
{
    public BrandsInstagramAccount $instagramAccount;

    public function mount(BrandsInstagramAccount $brandsInstagramAccount)
    {
        $this->instagramAccount = $brandsInstagramAccount->fresh();
        
        // Berechtigung prüfen
        $this->authorize('view', $this->instagramAccount);
    }

    #[On('updateInstagramAccount')] 
    public function updateInstagramAccount()
    {
        $this->instagramAccount->refresh();
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.instagram-account', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
