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

    /*
    |--------------------------------------------------------------------------
    | Meta OAuth Configuration
    |--------------------------------------------------------------------------
    |
    | Konfiguration für Facebook/Instagram OAuth über Socialite
    |
    */
    'meta' => [
        'client_id' => env('META_CLIENT_ID'),
        'client_secret' => env('META_CLIENT_SECRET'),
        'redirect' => env('META_OAUTH_REDIRECT_URI', '/brands/facebook-pages/oauth/callback'),
        'redirect_domain' => env('META_OAUTH_REDIRECT_DOMAIN'),
        'api_version' => env('META_API_VERSION', 'v21.0'),
    ],
];
