<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsSocialPlatform;
use Platform\Brands\Models\BrandsSocialPlatformFormat;

/**
 * Tool zum Auflisten von Social-Media-Plattform-Formaten.
 *
 * Formate sind eine lose Lookup-Tabelle — neue Formate werden zur Laufzeit
 * hinzugefügt ohne Code-Deployment. Filterbar nach platform_id, media_type, is_active.
 */
class ListSocialPlatformFormatsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.social_platform_formats.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/social_platform_formats - Listet Formate von Social-Media-Plattformen auf (z.B. Story, Post, Reel, Carousel). Formate sind eine lose Lookup-Tabelle — neue Formate werden zur Laufzeit hinzugefügt ohne Code-Deployment. Filterbar nach platform_id, media_type (image|video|carousel), is_active. REST-Parameter: platform_id (optional, filtert nach Plattform), media_type (optional), is_active (optional), search (optional), sort (optional), limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'platform_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Plattform-ID. Nutze "brands.social_platforms.GET" um Plattform-IDs zu sehen.',
                    ],
                    'media_type' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach Medientyp, z.B. "image", "video", "carousel".',
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Filter nach Aktivstatus. true = nur aktive, false = nur inaktive Formate.',
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

            $query = BrandsSocialPlatformFormat::query()
                ->with(['platform']);

            // Filter: platform_id
            if (isset($arguments['platform_id'])) {
                $platform = BrandsSocialPlatform::find($arguments['platform_id']);
                if (!$platform) {
                    return ToolResult::error('PLATFORM_NOT_FOUND', 'Die angegebene Plattform wurde nicht gefunden. Nutze "brands.social_platforms.GET" um Plattformen zu finden.');
                }
                $query->where('platform_id', $arguments['platform_id']);
            }

            // Filter: media_type
            if (isset($arguments['media_type'])) {
                $query->where('media_type', $arguments['media_type']);
            }

            // Filter: is_active
            if (isset($arguments['is_active'])) {
                $query->where('is_active', (bool) $arguments['is_active']);
            }

            // Standard-Filter
            $this->applyStandardFilters($query, $arguments, [
                'name', 'key', 'aspect_ratio', 'media_type', 'is_active', 'created_at', 'updated_at',
            ]);

            // Standard-Suche
            $this->applyStandardSearch($query, $arguments, ['name', 'key', 'media_type']);

            // Standard-Sortierung
            $this->applyStandardSort($query, $arguments, [
                'name', 'key', 'aspect_ratio', 'media_type', 'is_active', 'created_at', 'updated_at',
            ], 'name', 'asc');

            // Standard-Pagination
            $this->applyStandardPagination($query, $arguments);

            $formats = $query->get();

            $formatsList = $formats->map(function ($format) {
                return [
                    'id' => $format->id,
                    'platform_id' => $format->platform_id,
                    'platform_name' => $format->platform->name,
                    'platform_key' => $format->platform->key,
                    'name' => $format->name,
                    'key' => $format->key,
                    'aspect_ratio' => $format->aspect_ratio,
                    'media_type' => $format->media_type,
                    'is_active' => $format->is_active,
                    'created_at' => $format->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'formats' => $formatsList,
                'count' => count($formatsList),
                'message' => count($formatsList) > 0
                    ? count($formatsList) . ' Format(e) gefunden.'
                    : 'Keine Formate gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Formate: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'social', 'platform', 'format', 'list', 'lookup'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
