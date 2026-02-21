<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialPlatform;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen einer einzelnen Social-Media-Plattform inkl. aller Formate.
 */
class GetSocialPlatformTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_platform.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/social_platforms/{id} - Ruft eine einzelne Social-Media-Plattform ab inkl. aller zugehörigen Formate. REST-Parameter: id (required, integer) - Plattform-ID. Nutze "brands.social_platforms.GET" um verfügbare Plattform-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Plattform. Nutze "brands.social_platforms.GET" um verfügbare Plattform-IDs zu sehen.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Plattform-ID ist erforderlich. Nutze "brands.social_platforms.GET" um Plattformen zu finden.');
            }

            $platform = BrandsSocialPlatform::with(['formats' => function ($q) {
                $q->orderBy('name');
            }])->find($arguments['id']);

            if (!$platform) {
                return ToolResult::error('PLATFORM_NOT_FOUND', 'Die angegebene Plattform wurde nicht gefunden. Nutze "brands.social_platforms.GET" um alle verfügbaren Plattformen zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $platform);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Plattform (Policy).');
            }

            $data = [
                'id' => $platform->id,
                'name' => $platform->name,
                'key' => $platform->key,
                'is_active' => $platform->is_active,
                'team_id' => $platform->team_id,
                'formats_count' => $platform->formats->count(),
                'formats' => $platform->formats->map(function ($format) {
                    return [
                        'id' => $format->id,
                        'name' => $format->name,
                        'key' => $format->key,
                        'aspect_ratio' => $format->aspect_ratio,
                        'media_type' => $format->media_type,
                        'is_active' => $format->is_active,
                        'created_at' => $format->created_at->toIso8601String(),
                    ];
                })->values()->toArray(),
                'created_at' => $platform->created_at->toIso8601String(),
                'updated_at' => $platform->updated_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Plattform: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'social', 'platform', 'get', 'lookup'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
