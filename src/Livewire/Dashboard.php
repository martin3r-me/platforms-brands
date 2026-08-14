<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Services\BrandVerortungResolver;

class Dashboard extends Component
{
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

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
                'typographyBoards' => fn($q) => $q->with(['entries' => fn($e) => $e->limit(2)])->limit(1),
                'moodboardBoards',
                'socialBoards',
                'kanbanBoards',
                'logoBoards',
                'toneOfVoiceBoards',
                'personaBoards',
                'competitorBoards',
                'guidelineBoards',
                'seoBoards',
                'assetBoards',
                'contentBriefBoards',
            ])
            ->orderBy('name')
            ->get();

        // === VERORTUNG: Brand → Entity (+ Typ) via Organization-Dimension ===
        $verortung = $this->resolveVerortung($brands->pluck('id')->all());

        // === Enriched Rows ===
        $rows = $brands->map(function ($brand) use ($verortung) {
            $boardCount = $brand->ciBoards->count() + $brand->socialBoards->count() + $brand->kanbanBoards->count()
                + $brand->typographyBoards->count() + $brand->logoBoards->count() + $brand->toneOfVoiceBoards->count()
                + $brand->personaBoards->count() + $brand->competitorBoards->count() + $brand->guidelineBoards->count()
                + $brand->moodboardBoards->count() + $brand->seoBoards->count() + $brand->assetBoards->count()
                + $brand->contentBriefBoards->count();

            $v = $verortung[$brand->id] ?? null;

            return [
                'brand' => $brand,
                'ciBoard' => $brand->ciBoards->first(),
                'typographyBoard' => $brand->typographyBoards->first(),
                'boardCount' => $boardCount,
                'verortungEntity' => $v['entity'] ?? null,
                'verortungType' => $v['type'] ?? null,
                'verortungSort' => $v['sort'] ?? 999,
                'done' => (bool) $brand->done,
            ];
        });

        // === Sortierung ===
        $dir = $this->sortDirection === 'desc';
        $rows = $rows->sortBy(function ($row) {
            return match ($this->sortField) {
                'verortung' => sprintf('%03d-%s', $row['verortungSort'], $row['verortungEntity'] ?? 'zzz'),
                'boards' => str_pad((string) $row['boardCount'], 6, '0', STR_PAD_LEFT),
                'updated' => optional($row['brand']->updated_at)->timestamp ?? 0,
                'status' => $row['done'] ? '1' : '0',
                default => mb_strtolower($row['brand']->name),
            };
        }, SORT_NATURAL, $dir)->values();

        $activeCount = $brands->filter(fn($b) => !$b->done)->count();
        $archivedCount = $brands->count() - $activeCount;
        $totalBoards = $rows->sum('boardCount');
        $linkedCount = collect($verortung)->count();

        return view('brands::livewire.dashboard', [
            'rows' => $rows,
            'activeCount' => $activeCount,
            'archivedCount' => $archivedCount,
            'totalBrands' => $brands->count(),
            'totalBoards' => $totalBoards,
            'linkedCount' => $linkedCount,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ])->layout('platform::layouts.app');
    }

    /**
     * Ermittelt je Marke die Verortung (über engagement_with zum Kunden aufgelöst).
     *
     * @param  array<int>  $brandIds
     * @return array<int, array{entity: string, type: ?string, sort: int, via: ?string}>
     */
    protected function resolveVerortung(array $brandIds): array
    {
        return app(BrandVerortungResolver::class)->forBrandIds($brandIds);
    }
}
