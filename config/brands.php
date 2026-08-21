<?php

return [
    'routing' => [
        'mode' => env('BRANDS_MODE', 'path'),
        'prefix' => 'brands',
    ],
    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Font-Katalog (kuratiert, OFL / self-hostbar via @fontsource)
    |--------------------------------------------------------------------------
    | Single Source of Truth für die Typografie-Auswahl. Alle Fonts stehen unter
    | der SIL Open Font License – selbst hosten, bündeln, kommerziell nutzen ohne
    | Domain-Lizenz. Jede Marke wählt daraus; der Export trägt den fontsource-
    | Paketnamen mit, damit Entwickler & Flynk sie 1:1 self-hosten können.
    |
    | 'category' steuert Fallback-Stack + Gruppierung im Picker.
    | 'google'   = Family-Param nur für die Live-Vorschau im Modul.
    */
    'fonts' => [
        // ── Sans · neutral / UI ──
        ['key' => 'inter',            'label' => 'Inter',            'category' => 'sans',      'family' => 'Inter',            'fontsource' => '@fontsource-variable/inter',            'variable' => true,  'weights' => [400, 500, 600, 700], 'google' => 'Inter:wght@400;500;600;700'],
        ['key' => 'ibm-plex-sans',    'label' => 'IBM Plex Sans',    'category' => 'sans',      'family' => 'IBM Plex Sans',    'fontsource' => '@fontsource/ibm-plex-sans',             'variable' => false, 'weights' => [400, 500, 600, 700], 'google' => 'IBM+Plex+Sans:wght@400;500;600;700', 'family_group' => 'IBM Plex'],
        // ── Sans · humanistisch / warm ──
        ['key' => 'work-sans',        'label' => 'Work Sans',        'category' => 'sans',      'family' => 'Work Sans',        'fontsource' => '@fontsource-variable/work-sans',        'variable' => true,  'weights' => [400, 500, 600, 700], 'google' => 'Work+Sans:wght@400;500;600;700'],
        ['key' => 'dm-sans',          'label' => 'DM Sans',          'category' => 'sans',      'family' => 'DM Sans',          'fontsource' => '@fontsource-variable/dm-sans',          'variable' => true,  'weights' => [400, 500, 700],      'google' => 'DM+Sans:wght@400;500;700'],
        // ── Sans · modern / tech ──
        ['key' => 'manrope',          'label' => 'Manrope',          'category' => 'sans',      'family' => 'Manrope',          'fontsource' => '@fontsource-variable/manrope',          'variable' => true,  'weights' => [400, 500, 600, 700], 'google' => 'Manrope:wght@400;500;600;700'],
        ['key' => 'space-grotesk',    'label' => 'Space Grotesk',    'category' => 'sans',      'family' => 'Space Grotesk',    'fontsource' => '@fontsource-variable/space-grotesk',    'variable' => true,  'weights' => [400, 500, 700],      'google' => 'Space+Grotesk:wght@400;500;700'],
        // ── Rund / freundlich ──
        ['key' => 'nunito',           'label' => 'Nunito',           'category' => 'rounded',   'family' => 'Nunito',           'fontsource' => '@fontsource-variable/nunito',           'variable' => true,  'weights' => [400, 600, 700, 800], 'google' => 'Nunito:wght@400;600;700;800'],
        // ── Condensed ──
        ['key' => 'barlow-condensed', 'label' => 'Barlow Condensed', 'category' => 'condensed', 'family' => 'Barlow Condensed', 'fontsource' => '@fontsource/barlow-condensed',          'variable' => false, 'weights' => [400, 500, 600, 700], 'google' => 'Barlow+Condensed:wght@400;500;600;700'],
        // ── Serif · Fließtext ──
        ['key' => 'source-serif-4',   'label' => 'Source Serif 4',   'category' => 'serif',     'family' => 'Source Serif 4',   'fontsource' => '@fontsource-variable/source-serif-4',   'variable' => true,  'weights' => [400, 600, 700],      'google' => 'Source+Serif+4:wght@400;600;700'],
        ['key' => 'lora',             'label' => 'Lora',             'category' => 'serif',     'family' => 'Lora',             'fontsource' => '@fontsource-variable/lora',             'variable' => true,  'weights' => [400, 500, 600, 700], 'google' => 'Lora:wght@400;500;600;700'],
        ['key' => 'ibm-plex-serif',   'label' => 'IBM Plex Serif',   'category' => 'serif',     'family' => 'IBM Plex Serif',   'fontsource' => '@fontsource/ibm-plex-serif',            'variable' => false, 'weights' => [400, 500, 600, 700], 'google' => 'IBM+Plex+Serif:wght@400;500;600;700', 'family_group' => 'IBM Plex'],
        // ── Display-Serif ──
        ['key' => 'fraunces',         'label' => 'Fraunces',         'category' => 'display',   'family' => 'Fraunces',         'fontsource' => '@fontsource-variable/fraunces',         'variable' => true,  'weights' => [400, 500, 600, 700], 'google' => 'Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,400'],
        ['key' => 'playfair-display', 'label' => 'Playfair Display', 'category' => 'display',   'family' => 'Playfair Display', 'fontsource' => '@fontsource-variable/playfair-display', 'variable' => true,  'weights' => [400, 500, 600, 700], 'google' => 'Playfair+Display:wght@400;500;600;700'],
        // ── Slab ──
        ['key' => 'bitter',           'label' => 'Bitter',           'category' => 'slab',      'family' => 'Bitter',           'fontsource' => '@fontsource-variable/bitter',           'variable' => true,  'weights' => [400, 500, 600, 700], 'google' => 'Bitter:wght@400;500;600;700'],
        // ── Script · Akzent ──
        ['key' => 'caveat',           'label' => 'Caveat',           'category' => 'script',    'family' => 'Caveat',           'fontsource' => '@fontsource-variable/caveat',           'variable' => true,  'weights' => [400, 500, 600, 700], 'google' => 'Caveat:wght@400;500;600;700'],
        // ── Mono ──
        ['key' => 'jetbrains-mono',   'label' => 'JetBrains Mono',   'category' => 'mono',      'family' => 'JetBrains Mono',   'fontsource' => '@fontsource-variable/jetbrains-mono',   'variable' => true,  'weights' => [400, 500, 700],      'google' => 'JetBrains+Mono:wght@400;500;700'],
        ['key' => 'ibm-plex-mono',    'label' => 'IBM Plex Mono',    'category' => 'mono',      'family' => 'IBM Plex Mono',    'fontsource' => '@fontsource/ibm-plex-mono',             'variable' => false, 'weights' => [400, 500, 600, 700], 'google' => 'IBM+Plex+Mono:wght@400;500;600;700', 'family_group' => 'IBM Plex'],
    ],

    // Fallback-Stacks je Kategorie (für CSS font-family)
    'font_fallbacks' => [
        'sans'      => 'ui-sans-serif, system-ui, -apple-system, sans-serif',
        'serif'     => 'ui-serif, Georgia, Cambria, "Times New Roman", serif',
        'slab'      => 'ui-serif, Georgia, Cambria, serif',
        'display'   => 'ui-serif, Georgia, "Times New Roman", serif',
        'mono'      => 'ui-monospace, SFMono-Regular, Menlo, monospace',
        'rounded'   => 'ui-rounded, "SF Pro Rounded", ui-sans-serif, sans-serif',
        'condensed' => 'ui-sans-serif, system-ui, sans-serif',
        'script'    => '"Segoe Script", cursive',
    ],

    'font_categories' => [
        'sans'      => 'Sans-Serif',
        'serif'     => 'Serif',
        'slab'      => 'Slab-Serif',
        'display'   => 'Display',
        'mono'      => 'Monospace',
        'rounded'   => 'Rund',
        'condensed' => 'Condensed',
        'script'    => 'Script',
    ],

    // Aspekt-Tags für Website-Referenzen (Benchmark-Board)
    'reference_aspects' => [
        'layout'     => 'Layout',
        'typography' => 'Typografie',
        'color'      => 'Farbe',
        'imagery'    => 'Bildsprache',
        'tone'       => 'Tonalität',
        'motion'     => 'Motion/Interaktion',
        'navigation' => 'Navigation',
        'content'    => 'Content/Struktur',
    ],

    /*
    |--------------------------------------------------------------------------
    | Board-Typen (Single Source of Truth)
    |--------------------------------------------------------------------------
    | Zentrale Registry aller Board-Typen einer Marke. Steuert das generische
    | Anlegen (Brand::createBoard). Ein neuer Board-Typ = ein Eintrag hier
    | (+ Model, Route, View) – keine 14. copy-paste-Methode mehr.
    */
    'board_types' => [
        'ci'            => ['model' => \Platform\Brands\Models\BrandsCiBoard::class,           'name' => 'Neues CI Board',            'route' => 'brands.ci-boards.show'],
        'social'        => ['model' => \Platform\Brands\Models\BrandsSocialBoard::class,       'name' => 'Neues Social Board',        'route' => 'brands.social-boards.show'],
        'kanban'        => ['model' => \Platform\Brands\Models\BrandsKanbanBoard::class,       'name' => 'Neues Kanban Board',        'route' => 'brands.kanban-boards.show'],
        'typography'    => ['model' => \Platform\Brands\Models\BrandsTypographyBoard::class,   'name' => 'Neues Typografie Board',    'route' => 'brands.typography-boards.show'],
        'logo'          => ['model' => \Platform\Brands\Models\BrandsLogoBoard::class,         'name' => 'Neues Logo Board',          'route' => 'brands.logo-boards.show'],
        'tone-of-voice' => ['model' => \Platform\Brands\Models\BrandsToneOfVoiceBoard::class,  'name' => 'Neues Tone of Voice Board', 'route' => 'brands.tone-of-voice-boards.show'],
        'persona'       => ['model' => \Platform\Brands\Models\BrandsPersonaBoard::class,      'name' => 'Neues Persona Board',       'route' => 'brands.persona-boards.show'],
        'competitor'    => ['model' => \Platform\Brands\Models\BrandsCompetitorBoard::class,   'name' => 'Neues Wettbewerber Board',  'route' => 'brands.competitor-boards.show'],
        'reference'     => ['model' => \Platform\Brands\Models\BrandsReferenceBoard::class,    'name' => 'Neues Referenzen Board',    'route' => 'brands.reference-boards.show'],
        'guideline'     => ['model' => \Platform\Brands\Models\BrandsGuidelineBoard::class,    'name' => 'Neues Guidelines Board',    'route' => 'brands.guideline-boards.show'],
        'moodboard'     => ['model' => \Platform\Brands\Models\BrandsMoodboardBoard::class,    'name' => 'Neues Moodboard',           'route' => 'brands.moodboard-boards.show'],
        'asset'         => ['model' => \Platform\Brands\Models\BrandsAssetBoard::class,        'name' => 'Neues Asset Board',         'route' => 'brands.asset-boards.show'],
        'seo'           => ['model' => \Platform\Brands\Models\BrandsSeoBoard::class,          'name' => 'Neues SEO Board',           'route' => 'brands.seo-boards.show'],
        'content-brief' => ['model' => \Platform\Brands\Models\BrandsContentBriefBoard::class, 'name' => 'Neues Content Brief',       'route' => 'brands.content-brief-boards.show'],
    ],

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
