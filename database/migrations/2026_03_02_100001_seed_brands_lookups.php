<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Platform\Brands\Models\BrandsLookup;
use Platform\Brands\Models\BrandsLookupValue;

/**
 * Seeded initial Lookup-Werte für alle bestehenden Teams, die das Brands-Modul nutzen.
 * Lookups sind is_system=true und werden pro Team angelegt.
 */
return new class extends Migration
{
    private array $lookups = [
        'content_type' => [
            'label' => 'Content-Typ',
            'description' => 'Typ des Content Briefs (Pillar, Guide, How-To etc.)',
            'values' => [
                ['value' => 'homepage', 'label' => 'Homepage', 'order' => 1],
                ['value' => 'pillar', 'label' => 'Pillar Page', 'order' => 2],
                ['value' => 'guide', 'label' => 'Guide', 'order' => 3],
                ['value' => 'how-to', 'label' => 'How-To', 'order' => 4],
                ['value' => 'listicle', 'label' => 'Listicle', 'order' => 5],
                ['value' => 'comparison', 'label' => 'Comparison', 'order' => 6],
                ['value' => 'review', 'label' => 'Review', 'order' => 7],
                ['value' => 'faq', 'label' => 'FAQ', 'order' => 8],
                ['value' => 'landing-page', 'label' => 'Landing Page', 'order' => 9],
                ['value' => 'glossary', 'label' => 'Glossary', 'order' => 10],
                ['value' => 'case-study', 'label' => 'Case Study', 'order' => 11],
                ['value' => 'deep-dive', 'label' => 'Deep-Dive', 'order' => 12],
            ],
        ],
        'search_intent' => [
            'label' => 'Such-Intent',
            'description' => 'Such-Intention des Content Briefs',
            'values' => [
                ['value' => 'informational', 'label' => 'Informational', 'order' => 1],
                ['value' => 'commercial', 'label' => 'Commercial', 'order' => 2],
                ['value' => 'transactional', 'label' => 'Transactional', 'order' => 3],
                ['value' => 'navigational', 'label' => 'Navigational', 'order' => 4],
                ['value' => 'branded', 'label' => 'Branded', 'order' => 5],
            ],
        ],
        'content_brief_status' => [
            'label' => 'Content Brief Status',
            'description' => 'Workflow-Status eines Content Briefs',
            'values' => [
                ['value' => 'draft', 'label' => 'Entwurf', 'order' => 1],
                ['value' => 'briefed', 'label' => 'Gebrieft', 'order' => 2],
                ['value' => 'in_production', 'label' => 'In Produktion', 'order' => 3],
                ['value' => 'review', 'label' => 'Review', 'order' => 4],
                ['value' => 'published', 'label' => 'Veröffentlicht', 'order' => 5],
            ],
        ],
        'revision_type' => [
            'label' => 'Revision-Typ',
            'description' => 'Art der Content-Änderung an einem veröffentlichten Brief',
            'values' => [
                ['value' => 'initial_publish', 'label' => 'Erstveröffentlichung', 'order' => 1],
                ['value' => 'optimization', 'label' => 'Optimierung', 'order' => 2],
                ['value' => 'extension', 'label' => 'Erweiterung', 'order' => 3],
                ['value' => 'rewrite', 'label' => 'Umschreibung', 'order' => 4],
                ['value' => 'structure_change', 'label' => 'Strukturänderung', 'order' => 5],
                ['value' => 'link_update', 'label' => 'Link-Update', 'order' => 6],
                ['value' => 'seo_fix', 'label' => 'SEO-Fix', 'order' => 7],
            ],
        ],
    ];

    public function up(): void
    {
        // Alle Teams ermitteln, die brands_brands haben
        $teamIds = DB::table('brands_brands')
            ->distinct()
            ->pluck('team_id')
            ->toArray();

        // Falls keine Teams, trotzdem nichts tun (frische Installation)
        if (empty($teamIds)) {
            return;
        }

        foreach ($teamIds as $teamId) {
            foreach ($this->lookups as $name => $config) {
                $lookup = BrandsLookup::firstOrCreate(
                    ['team_id' => $teamId, 'name' => $name],
                    [
                        'label' => $config['label'],
                        'description' => $config['description'],
                        'is_system' => true,
                    ]
                );

                foreach ($config['values'] as $val) {
                    BrandsLookupValue::firstOrCreate(
                        ['lookup_id' => $lookup->id, 'value' => $val['value']],
                        [
                            'label' => $val['label'],
                            'order' => $val['order'],
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // Nur System-Lookups entfernen (Custom-Lookups bleiben)
        BrandsLookup::where('is_system', true)
            ->whereIn('name', array_keys($this->lookups))
            ->delete(); // cascade löscht auch values
    }
};
