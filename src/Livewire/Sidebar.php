<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsBrand;
use Platform\Organization\Models\OrganizationContext;
use Platform\Organization\Models\OrganizationEntityLink;
use Platform\Organization\Models\OrganizationEntity;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllBrands = false;

    public function mount()
    {
        $this->showAllBrands = false;
    }

    #[On('updateSidebar')]
    public function updateSidebar()
    {
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

        $this->authorize('create', BrandsBrand::class);

        $brand = BrandsBrand::create([
            'name' => 'Neue Marke',
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        $this->dispatch('updateSidebar');

        return $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return view('brands::livewire.sidebar', [
                'entityTypeGroups' => collect(),
                'unlinkedBrands' => collect(),
                'hasMoreBrands' => false,
            ]);
        }

        // Alle Marken des Teams
        $allBrands = BrandsBrand::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get();

        $brandsToShow = $allBrands;
        $hasMoreBrands = false;

        // Entity-Verknüpfungen laden aus beiden Quellen
        $brandIds = $brandsToShow->pluck('id')->toArray();
        $entityBrandMap = [];
        $linkedBrandIds = [];

        // a) OrganizationContext (primäre Quelle – UI)
        $contextMorphTypes = ['brand', 'brands_brand', BrandsBrand::class];
        $contexts = OrganizationContext::query()
            ->whereIn('contextable_type', $contextMorphTypes)
            ->whereIn('contextable_id', $brandIds)
            ->where('is_active', true)
            ->with(['organizationEntity.type'])
            ->get();

        foreach ($contexts as $ctx) {
            $entityId = $ctx->organization_entity_id;
            $brandId = $ctx->contextable_id;
            if ($entityId) {
                $entityBrandMap[$entityId][] = $brandId;
                $linkedBrandIds[] = $brandId;
            }
        }

        // b) OrganizationEntityLink (sekundäre Quelle – DimensionLinker / LLM Tools)
        $entityLinks = OrganizationEntityLink::query()
            ->whereIn('linkable_type', $contextMorphTypes)
            ->whereIn('linkable_id', $brandIds)
            ->with(['entity.type'])
            ->get();

        foreach ($entityLinks as $link) {
            $entityId = $link->entity_id;
            $brandId = $link->linkable_id;
            $entityBrandMap[$entityId][] = $brandId;
            $linkedBrandIds[] = $brandId;
        }

        // Deduplizieren
        foreach ($entityBrandMap as $entityId => $bids) {
            $entityBrandMap[$entityId] = array_unique($bids);
        }
        $linkedBrandIds = array_unique($linkedBrandIds);

        // Gruppieren: EntityType → Entity → Brands
        $entityTypeGroups = collect();
        $entityIds = array_keys($entityBrandMap);

        if (!empty($entityIds)) {
            $entities = OrganizationEntity::with('type')
                ->whereIn('id', $entityIds)
                ->get()
                ->keyBy('id');

            $groupedByType = [];
            foreach ($entityBrandMap as $entityId => $brandIdsList) {
                $entity = $entities->get($entityId);
                if (!$entity || !$entity->type) {
                    continue;
                }
                $typeId = $entity->type->id;
                if (!isset($groupedByType[$typeId])) {
                    $groupedByType[$typeId] = [
                        'type_id' => $typeId,
                        'type_name' => $entity->type->name,
                        'type_icon' => $entity->type->icon,
                        'sort_order' => $entity->type->sort_order ?? 999,
                        'entities' => [],
                    ];
                }
                if (!isset($groupedByType[$typeId]['entities'][$entityId])) {
                    $groupedByType[$typeId]['entities'][$entityId] = [
                        'entity_id' => $entityId,
                        'entity_name' => $entity->name,
                        'brands' => collect(),
                    ];
                }
                foreach ($brandIdsList as $bid) {
                    $brand = $brandsToShow->firstWhere('id', $bid);
                    if ($brand) {
                        $groupedByType[$typeId]['entities'][$entityId]['brands']->push($brand);
                    }
                }
            }

            $entityTypeGroups = collect($groupedByType)
                ->sortBy('sort_order')
                ->map(function ($group) {
                    $group['entities'] = collect($group['entities'])
                        ->sortBy('entity_name')
                        ->values();
                    return $group;
                })
                ->values();
        }

        // Unverknüpfte Marken
        $unlinkedBrands = $brandsToShow->filter(function ($brand) use ($linkedBrandIds) {
            return !in_array($brand->id, $linkedBrandIds);
        })->values();

        return view('brands::livewire.sidebar', [
            'entityTypeGroups' => $entityTypeGroups,
            'unlinkedBrands' => $unlinkedBrands,
            'hasMoreBrands' => $hasMoreBrands,
            'allBrandsCount' => $allBrands->count(),
        ]);
    }
}
