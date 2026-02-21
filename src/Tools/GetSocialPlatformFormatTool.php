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
        return 'GET /brands/social_platform_formats/{id} - Ruft ein einzelnes Social-Media-Plattform-Format ab inkl. output_schema (JSON-Contract für Worker-Output), rules (weiche Regeln) und verknüpfte Personas (Audience-Kontext mit Demografie, Pain Points, Goals, Behaviors). REST-Parameter: id (required, integer) - Format-ID. Nutze "brands.social_platform_formats.GET" um verfügbare Format-IDs zu sehen. Worker-Workflow: 1) Format laden (output_schema + rules + Personas), 2) Personas-Profil auswerten (Ton, Wortwahl, Komplexität anpassen), 3) Content gegen output_schema produzieren (Felder, Typen, Limits beachten), 4) rules für Feinsteuerung anwenden, 5) Schema + Rules + Personas = vollständiges Produktions-Briefing.';
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

            $format = BrandsSocialPlatformFormat::with(['platform', 'personas'])
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
                'personas' => $format->personas->map(function ($persona) {
                    return [
                        'id' => $persona->id,
                        'name' => $persona->name,
                        'age' => $persona->age,
                        'gender' => $persona->gender,
                        'occupation' => $persona->occupation,
                        'bio' => $persona->bio,
                        'pain_points' => $persona->pain_points,
                        'goals' => $persona->goals,
                        'behaviors' => $persona->behaviors,
                        'channels' => $persona->channels,
                        'notes' => $persona->pivot->notes,
                    ];
                })->values()->toArray(),
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
