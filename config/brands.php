<?php

return [
    'routing' => [
        'mode' => env('BRANDS_MODE', 'path'),
        'prefix' => 'brands',
    ],
    'guard' => 'web',

    'navigation' => [
        'route' => 'brands.dashboard',
        'icon'  => 'heroicon-o-tag',
        'order' => 30,
    ],

    'sidebar' => [
        [
            'group' => 'Marken',
            'dynamic' => [
                'model'     => \Platform\Brands\Models\BrandsBrand::class,
                'team_based' => true,
                'order_by'  => 'name',
                'route'     => 'brands.brands.show',
                'icon'      => 'heroicon-o-tag',
                'label_key' => 'name',
            ],
        ],
    ],
    'billables' => [],
];
