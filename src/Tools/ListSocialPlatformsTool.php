<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsSocialPlatform;

/**
 * Tool zum Auflisten aller Social-Media-Plattformen.
 *
 * Social Platforms sind eine lose Lookup-Tabelle — kein Enum, keine Hardcodierung.
 * Neue Plattformen werden zur Laufzeit hinzugefügt ohne Code-Deployment.
 */
class ListSocialPlatformsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.social_platforms.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/social_platforms - Listet alle Social-Media-Plattformen auf (z.B. Instagram, Facebook, LinkedIn, TikTok etc.). Social Platforms sind eine lose Lookup-Tabelle — neue Plattformen können zur Laufzeit hinzugefügt werden ohne Code-Deployment. Filterbar nach is_active. REST-Parameter: is_active (optional), search (optional), sort (optional), limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Filter nach Aktivstatus. true = nur aktive, false = nur inaktive Plattformen.',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $query = BrandsSocialPlatform::query()
                ->with(['formats' => function ($q) {
                    $q->where('is_active', true)->orderBy('name');
                }]);

            // Filter: is_active
            if (isset($arguments['is_active'])) {
                $query->where('is_active', (bool) $arguments['is_active']);
            }

            // Standard-Filter
            $this->applyStandardFilters($query, $arguments, [
                'name', 'key', 'is_active', 'created_at', 'updated_at',
            ]);

            // Standard-Suche
            $this->applyStandardSearch($query, $arguments, ['name', 'key']);

            // Standard-Sortierung
            $this->applyStandardSort($query, $arguments, [
                'name', 'key', 'is_active', 'created_at', 'updated_at',
            ], 'name', 'asc');

            // Standard-Pagination
            $this->applyStandardPagination($query, $arguments);

            $platforms = $query->get();

            $platformsList = $platforms->map(function ($platform) {
                return [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'key' => $platform->key,
                    'is_active' => $platform->is_active,
                    'formats_count' => $platform->formats->count(),
                    'formats' => $platform->formats->map(function ($format) {
                        return [
                            'id' => $format->id,
                            'name' => $format->name,
                            'key' => $format->key,
                            'aspect_ratio' => $format->aspect_ratio,
                            'media_type' => $format->media_type,
                        ];
                    })->values()->toArray(),
                    'created_at' => $platform->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'platforms' => $platformsList,
                'count' => count($platformsList),
                'message' => count($platformsList) > 0
                    ? count($platformsList) . ' Plattform(en) gefunden.'
                    : 'Keine Plattformen gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Plattformen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'social', 'platform', 'list', 'lookup'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
