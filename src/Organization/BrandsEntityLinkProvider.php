<?php

namespace Platform\Brands\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Organization\Contracts\EntityLinkProvider;

class BrandsEntityLinkProvider implements EntityLinkProvider
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
        return [];
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }
}
