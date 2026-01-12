<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsBrand;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllBrands = false;

    public function mount()
    {
        // Zustand aus localStorage laden (wird vom Frontend gesetzt)
        $this->showAllBrands = false; // Default-Wert, wird vom Frontend überschrieben
    }

    #[On('updateSidebar')] 
    public function updateSidebar()
    {
        // Wird später implementiert
    }

    public function toggleShowAllBrands()
    {
        $this->showAllBrands = !$this->showAllBrands;
    }

    public function createBrand()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            return;
        }

        // Policy-Berechtigung prüfen
        $this->authorize('create', BrandsBrand::class);

        // Neue Marke anlegen
        $brand = BrandsBrand::create([
            'name' => 'Neue Marke',
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        $this->dispatch('updateSidebar');
        
        // Zur Marken-Ansicht weiterleiten
        return $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return view('brands::livewire.sidebar', [
                'brands' => collect(),
                'hasMoreBrands' => false,
                'allBrandsCount' => 0,
            ]);
        }

        // Alle Marken des Teams
        $allBrands = BrandsBrand::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get();

        // Marken filtern: alle oder nur bestimmte (später erweiterbar)
        $brandsToShow = $this->showAllBrands 
            ? $allBrands 
            : $allBrands; // Später: nur Marken mit bestimmten Kriterien

        $hasMoreBrands = false; // Später: wenn Filter-Logik implementiert wird

        return view('brands::livewire.sidebar', [
            'brands' => $brandsToShow,
            'hasMoreBrands' => $hasMoreBrands,
            'allBrandsCount' => $allBrands->count(),
        ]);
    }
}
