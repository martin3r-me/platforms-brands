<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsBrand;
use Platform\Organization\Services\EntityDimensionBridge;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Models\OrganizationEntityRelationship;
use Platform\Organization\Models\OrganizationEntityRelationType;

/**
 * Löst die organisatorische Verortung einer Marke auf.
 *
 * Marken hängen am DIGITAL.AGENCY-Kunden-Engagement (nicht direkt am Kunden).
 * Analog zur SEO-Sidebar (customerForNode) wird der Engagement-Link über die
 * Relation `engagement_with` zum eigentlichen Kunden aufgelöst — so zeigt die
 * Verortung den Kunden statt eines beliebigen (z. B. Website-)Links.
 *
 * Guarded: leeres Ergebnis, wenn das Organization-Modul nicht geladen ist.
 */
class BrandVerortungResolver
{
    /**
     * @param  int[]  $brandIds
     * @return array<int, array{entity:string, type:?string, sort:int, via:?string}>
     */
    public function forBrandIds(array $brandIds): array
    {
        if (empty($brandIds) || !class_exists(EntityDimensionBridge::class)) {
            return [];
        }

        // 1. Marke → verknüpfte Entity-IDs (in Link-Reihenfolge)
        $morphs = ['brand', 'brands_brand', BrandsBrand::class];
        $brandEntityIds = [];
        foreach (EntityDimensionBridge::linksForLinkables($morphs, $brandIds) as $link) {
            $brandEntityIds[$link->linkable_id][] = (int) $link->entity_id;
        }
        if (empty($brandEntityIds)) {
            return [];
        }

        // 2. engagement_with: Engagement (from) → Kunde (to)
        $engagementToCustomer = [];
        $typeId = OrganizationEntityRelationType::where('code', 'engagement_with')->value('id');
        if ($typeId) {
            foreach (OrganizationEntityRelationship::where('relation_type_id', $typeId)->get(['from_entity_id', 'to_entity_id']) as $rel) {
                $engagementToCustomer[(int) $rel->from_entity_id] = (int) $rel->to_entity_id;
            }
        }

        // 3. Ziel je Marke bestimmen: bevorzugt der über ein Engagement aufgelöste
        //    Kunde, sonst der erste direkte Link (z. B. Venture-/Root-Knoten).
        $targetByBrand = [];
        $neededEntityIds = [];
        foreach ($brandEntityIds as $brandId => $entityIds) {
            $target = null;
            $via = null;
            foreach ($entityIds as $eid) {
                if (isset($engagementToCustomer[$eid])) {
                    $target = $engagementToCustomer[$eid];
                    $via = $eid;
                    break;
                }
            }
            if ($target === null) {
                $target = $entityIds[0] ?? null;
            }
            if ($target === null) {
                continue;
            }
            $targetByBrand[$brandId] = ['target' => $target, 'via' => $via];
            $neededEntityIds[] = $target;
            if ($via) {
                $neededEntityIds[] = $via;
            }
        }

        if (empty($neededEntityIds)) {
            return [];
        }

        // 4. Ziel-Entities (Kunden + Engagements) mit Typ laden
        $entities = OrganizationEntity::with('type')
            ->whereIn('id', array_unique($neededEntityIds))
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($targetByBrand as $brandId => $info) {
            $entity = $entities->get($info['target']);
            if (!$entity) {
                continue;
            }
            $via = $info['via'] ? $entities->get($info['via']) : null;
            $result[$brandId] = [
                'entity' => $entity->name,
                'type' => $entity->type?->name,
                'sort' => $entity->type?->sort_order ?? 999,
                'via' => $via?->name,
            ];
        }

        return $result;
    }
}
