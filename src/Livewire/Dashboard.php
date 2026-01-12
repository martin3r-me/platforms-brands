<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsBrand;

class Dashboard extends Component
{
    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => 'Platform\Brands\Models\BrandsBrand',
            'modelId' => null,
            'subject' => 'Brands Dashboard',
            'description' => 'Übersicht aller Marken',
            'url' => route('brands.dashboard'),
            'source' => 'brands.dashboard',
            'recipients' => [],
            'meta' => [
                'view_type' => 'dashboard',
            ],
        ]);
    }

    public function createBrand()
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        // Neue Marke anlegen
        $brand = BrandsBrand::create([
            'name' => 'Neue Marke',
            'user_id' => $user->id,
            'team_id' => $teamId,
        ]);

        $this->dispatch('updateSidebar');
        
        // Zur Marken-Ansicht weiterleiten
        return redirect()->route('brands.brands.show', $brand);
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        
        // === MARKEN (nur Team-Marken) ===
        $brands = BrandsBrand::where('team_id', $team->id)->orderBy('name')->get();
        $activeBrands = $brands->filter(function($brand) {
            return $brand->done === null || $brand->done === false;
        })->count();
        $totalBrands = $brands->count();

        // === MARKEN-ÜBERSICHT (nur aktive Marken) ===
        $activeBrandsList = $brands->filter(function($brand) {
            return $brand->done === null || $brand->done === false;
        })
        ->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'subtitle' => $brand->description ? mb_substr($brand->description, 0, 50) . '...' : '',
            ];
        })
        ->take(5);

        return view('brands::livewire.dashboard', [
            'activeBrands' => $activeBrands,
            'totalBrands' => $totalBrands,
            'activeBrandsList' => $activeBrandsList,
        ])->layout('platform::layouts.app');
    }
}
