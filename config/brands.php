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
    'billables' => [
        [
            'model' => \Platform\Brands\Models\BrandsBrand::class,
            'type' => 'per_item',
            'label' => 'Marke',
            'description' => 'Jede angelegte Marke verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                ['cost_per_day' => 0.01, 'start_date' => '2025-01-01', 'end_date' => null]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
    ],
];
