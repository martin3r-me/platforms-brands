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
        
        // Policy-Berechtigung prüfen
        $this->authorize('create', BrandsBrand::class);

        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

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
        $user = Auth::user();
        $team = $user->currentTeam;
        
        // === MARKEN mit Board-Daten für Preview ===
        $brands = BrandsBrand::where('team_id', $team->id)
            ->with([
                'ciBoards' => fn($q) => $q->with(['colors' => fn($c) => $c->limit(5)])->limit(1),
                'moodboardBoards' => fn($q) => $q->with(['images' => fn($i) => $i->limit(4)])->limit(1),
                'personaBoards' => fn($q) => $q->withCount('personas')->limit(1),
                'typographyBoards' => fn($q) => $q->with(['entries' => fn($e) => $e->limit(2)])->limit(1),
                'socialBoards',
                'kanbanBoards',
                'logoBoards',
                'toneOfVoiceBoards',
                'competitorBoards',
                'guidelineBoards',
                'seoBoards',
                'assetBoards',
                'contentBriefBoards',
            ])
            ->orderBy('name')
            ->get();

        $activeBrands = $brands->filter(fn($b) => !$b->done)->count();
        $totalBrands = $brands->count();
        $totalBoards = $brands->sum(function($brand) {
            return $brand->ciBoards->count() + $brand->socialBoards->count() + $brand->kanbanBoards->count()
                + $brand->typographyBoards->count() + $brand->logoBoards->count() + $brand->toneOfVoiceBoards->count()
                + $brand->personaBoards->count() + $brand->competitorBoards->count() + $brand->guidelineBoards->count()
                + $brand->moodboardBoards->count() + $brand->seoBoards->count() + $brand->assetBoards->count()
                + $brand->contentBriefBoards->count();
        });

        $activeBrandsList = $brands->filter(fn($b) => !$b->done);
        $doneBrandsList = $brands->filter(fn($b) => $b->done);

        return view('brands::livewire.dashboard', [
            'activeBrands' => $activeBrands,
            'totalBrands' => $totalBrands,
            'totalBoards' => $totalBoards,
            'activeBrandsList' => $activeBrandsList,
            'doneBrandsList' => $doneBrandsList,
        ])->layout('platform::layouts.app');
    }
}
