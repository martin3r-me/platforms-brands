<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Integrations\Services\DataForSeoApiService;

class SearchDataForSeoLocationsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.dataforseo_locations.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/dataforseo_locations - Sucht verfügbare DataForSEO-Locations (Länder, Regionen, Städte) für SERP-Tracking. Kostenlos, kein Credit-Verbrauch. Parameter: country (optional, string, ISO-2 z.B. "DE"), search (optional, string, Suchbegriff z.B. "Düsseldorf"). Ergebnis: location_code + location_name für die Board-Konfiguration.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'country' => [
                    'type' => 'string',
                    'description' => 'ISO-2 Ländercode zum Filtern (z.B. "DE" = Deutschland, "AT" = Österreich, "CH" = Schweiz). Ohne country werden alle Länder zurückgegeben.',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Suchbegriff zum Filtern der Ergebnisse (z.B. "Düsseldorf", "Bayern", "Berlin"). Case-insensitive.',
                ],
                'location_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Typ filtern. Möglich: "Country", "Region", "City", "County", "DMA Region" etc.',
                ],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $country = $arguments['country'] ?? null;
            $search = $arguments['search'] ?? null;
            $locationType = $arguments['location_type'] ?? null;

            $api = app(DataForSeoApiService::class);
            $locations = $api->getLocations($context->user, $country);

            // Client-seitig filtern nach Suchbegriff
            if ($search) {
                $searchLower = mb_strtolower($search);
                $locations = array_filter($locations, function ($loc) use ($searchLower) {
                    return str_contains(mb_strtolower($loc['location_name'] ?? ''), $searchLower);
                });
            }

            // Nach location_type filtern
            if ($locationType) {
                $locations = array_filter($locations, function ($loc) use ($locationType) {
                    return ($loc['location_type'] ?? '') === $locationType;
                });
            }

            $locations = array_values($locations);

            // Auf 50 Ergebnisse limitieren
            $total = count($locations);
            $locations = array_slice($locations, 0, 50);

            return ToolResult::success([
                'locations' => array_map(fn($loc) => [
                    'location_code' => $loc['location_code'],
                    'location_name' => $loc['location_name'],
                    'country_iso_code' => $loc['country_iso_code'],
                    'location_type' => $loc['location_type'],
                ], $locations),
                'total' => $total,
                'showing' => count($locations),
                'hint' => $total > 50
                    ? "Es gibt {$total} Treffer. Nutze 'search' oder 'location_type' zum Eingrenzen."
                    : null,
                'usage' => 'Nutze location_code + location_name um Locations auf einem SEO Board zu konfigurieren via brands.seo_boards.PUT mit dataforseo_config.locations Array.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Abrufen der Locations: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'lookup',
            'tags' => ['brands', 'seo', 'dataforseo', 'locations', 'regions'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'read',
            'idempotent' => true,
        ];
    }
}
