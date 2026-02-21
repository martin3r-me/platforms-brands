<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialPlatformFormat;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen Social-Media-Plattform-Formats.
 */
class GetSocialPlatformFormatTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_platform_format.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/social_platform_formats/{id} - Ruft ein einzelnes Social-Media-Plattform-Format ab inkl. output_schema (JSON-Contract für Worker-Output) und rules (weiche Regeln). REST-Parameter: id (required, integer) - Format-ID. Nutze "brands.social_platform_formats.GET" um verfügbare Format-IDs zu sehen. Worker-Workflow: 1) Format + output_schema über dieses Tool laden, 2) Content gegen output_schema produzieren (Felder, Typen, Limits beachten), 3) rules für Feinsteuerung anwenden (z.B. allows_links, hashtag_style, tone_adjustment), 4) Ergebnis gegen output_schema validieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Formats. Nutze "brands.social_platform_formats.GET" um verfügbare Format-IDs zu sehen.',
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
                return ToolResult::error('VALIDATION_ERROR', 'Format-ID ist erforderlich. Nutze "brands.social_platform_formats.GET" um Formate zu finden.');
            }

            $format = BrandsSocialPlatformFormat::with(['platform'])
                ->find($arguments['id']);

            if (!$format) {
                return ToolResult::error('FORMAT_NOT_FOUND', 'Das angegebene Format wurde nicht gefunden. Nutze "brands.social_platform_formats.GET" um alle verfügbaren Formate zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $format);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Format (Policy).');
            }

            $data = [
                'id' => $format->id,
                'platform_id' => $format->platform_id,
                'platform_name' => $format->platform->name,
                'platform_key' => $format->platform->key,
                'name' => $format->name,
                'key' => $format->key,
                'aspect_ratio' => $format->aspect_ratio,
                'media_type' => $format->media_type,
                'output_schema' => $format->output_schema,
                'rules' => $format->rules,
                'is_active' => $format->is_active,
                'team_id' => $format->team_id,
                'created_at' => $format->created_at->toIso8601String(),
                'updated_at' => $format->updated_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Formats: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'social', 'platform', 'format', 'get', 'lookup'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
