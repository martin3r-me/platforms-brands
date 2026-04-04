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

        // Aufwärts-Traversierung: Ancestors ins Entity-Set aufnehmen
        $directEntityIds = array_keys($entityBrandMap);
        if (!empty($directEntityIds)) {
            $directEntities = OrganizationEntity::with(['allParents.type'])
                ->whereIn('id', $directEntityIds)
                ->get()
                ->keyBy('id');

            foreach ($directEntities as $entityId => $entity) {
                $ancestor = $entity->allParents;
                while ($ancestor) {
                    if (!isset($entityBrandMap[$ancestor->id])) {
                        $entityBrandMap[$ancestor->id] = [];
                    }
                    $ancestor = $ancestor->allParents;
                }
            }
        }

        // Gruppieren: EntityType → Entity-Baum → Brands
        $entityTypeGroups = collect();
        $entityIds = array_keys($entityBrandMap);

        if (!empty($entityIds)) {
            $entities = OrganizationEntity::with('type')
                ->whereIn('id', $entityIds)
                ->get()
                ->keyBy('id');

            $entityChildrenMap = [];
            $rootEntityIds = [];

            foreach ($entities as $entity) {
                $parentId = $entity->parent_entity_id;
                if ($parentId && $entities->has($parentId)) {
                    $entityChildrenMap[$parentId][] = $entity->id;
                } else {
                    $rootEntityIds[] = $entity->id;
                }
            }

            $buildTree = function (int $entityId) use (&$buildTree, $entities, $entityChildrenMap, $entityBrandMap, $brandsToShow): ?array {
                $entity = $entities->get($entityId);
                if (!$entity) {
                    return null;
                }

                $childIds = $entityChildrenMap[$entityId] ?? [];
                $childNodes = collect($childIds)
                    ->map(fn ($childId) => $buildTree($childId))
                    ->filter();

                $childrenByType = $childNodes
                    ->groupBy(fn ($child) => $child['type_id'])
                    ->map(function ($group) use ($entities) {
                        $firstChild = $group->first();
                        $typeEntity = $entities->get($firstChild['entity_id']);
                        $type = $typeEntity?->type;

                        return [
                            'type_id' => $firstChild['type_id'],
                            'type_name' => $type?->name ?? 'Sonstige',
                            'type_icon' => $type?->icon ?? null,
                            'sort_order' => $type?->sort_order ?? 999,
                            'children' => $group->sortBy('entity_name')->values(),
                        ];
                    })
                    ->sortBy('sort_order')
                    ->values();

                $items = collect($entityBrandMap[$entityId] ?? [])
                    ->map(fn ($bid) => $brandsToShow->firstWhere('id', $bid))
                    ->filter()
                    ->values();

                $totalItems = $items->count();
                foreach ($childNodes as $child) {
                    $totalItems += $child['total_items'];
                }

                if ($totalItems === 0) {
                    return null;
                }

                return [
                    'entity_id' => $entityId,
                    'entity_name' => $entity->name,
                    'type_id' => $entity->type?->id,
                    'items' => $items,
                    'children_by_type' => $childrenByType,
                    'total_items' => $totalItems,
                ];
            };

            $groupedByType = [];
            foreach ($rootEntityIds as $entityId) {
                $entity = $entities->get($entityId);
                if (!$entity || !$entity->type) {
                    continue;
                }

                $tree = $buildTree($entityId);
                if (!$tree) {
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
                $groupedByType[$typeId]['entities'][] = $tree;
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
