<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsBrand;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        
        // Erste Marke des Teams laden oder null
        $firstBrand = BrandsBrand::where('team_id', $user->currentTeam?->id)
            ->orderBy('name')
            ->first();

        return view('brands::livewire.dashboard', [
            'firstBrand' => $firstBrand,
        ])->layout('platform::layouts.app');
    }
}
