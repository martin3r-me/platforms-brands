<?php

namespace Platform\Brands\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Brands\Models\BrandsBrand;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Organization\Contracts\HasMetricDefinitions;

class BrandsEntityLinkProvider implements EntityLinkProvider, HasMetricDefinitions
{
    public function morphAliases(): array
    {
        return ['brands_brand'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'brands_brand' => [
                'label' => 'Marken',
                'singular' => 'Marke',
                'icon' => 'sparkles',
                'route' => 'brands.brands.show',
            ],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        // Keine speziellen Eager-Loadings nötig.
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        return [];
    }

    public function metadataDisplayRules(): array
    {
        return [];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        if ($morphAlias !== 'brands_brand') {
            return [];
        }

        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        $brands = BrandsBrand::whereIn('id', $allIds)
            ->select('id', 'done')
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $total = 0;
            $active = 0;
            $done = 0;

            foreach ($ids as $id) {
                $b = $brands[$id] ?? null;
                if (! $b) {
                    continue;
                }
                $total++;
                if ($b->done) {
                    $done++;
                } else {
                    $active++;
                }
            }

            $result[$entityId] = [
                'brands_brands_total' => $total,
                'brands_brands_active' => $active,
                'brands_brands_done' => $done,
            ];
        }

        return $result;
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }

    public function metricDefinitions(): array
    {
        return [
            'brands_brands_total'  => ['label' => 'Marken (gesamt)', 'group' => 'brands', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'org_capital', 'type' => 'stock', 'aggregation_mode' => 'rolled_up', 'basis' => 'stichtag'],
            'brands_brands_active' => ['label' => 'Marken (aktiv)', 'group' => 'brands', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'org_capital', 'type' => 'stock', 'aggregation_mode' => 'rolled_up', 'basis' => 'stichtag'],
            'brands_brands_done'   => ['label' => 'Marken (erledigt)', 'group' => 'brands', 'direction' => 'up', 'unit' => 'count', 'pair' => 'brands_brands_total', 'dimension' => 'throughput', 'type' => 'flow', 'aggregation_mode' => 'rolled_up', 'basis' => 'cumulative_since_start'],
        ];
    }
}
