<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialPlatform;
use Platform\Brands\Models\BrandsSocialPlatformFormat;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen eines neuen Formats für eine Social-Media-Plattform.
 *
 * Formate sind eine lose Lookup-Tabelle — neue Formate werden zur Laufzeit
 * hinzugefügt ohne Code-Deployment.
 */
class CreateSocialPlatformFormatTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_platform_formats.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/social_platform_formats - Erstellt ein neues Format für eine Social-Media-Plattform (z.B. "Story" für Instagram). Formate sind eine lose Lookup-Tabelle — neue Formate werden zur Laufzeit hinzugefügt ohne Code-Deployment. REST-Parameter: platform_id (required), name (required), key (required, unique pro Plattform), aspect_ratio (optional, z.B. "9:16", "1:1"), media_type (optional, z.B. "image", "video", "carousel"), is_active (optional, default true).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'platform_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Plattform (ERFORDERLICH). Nutze "brands.social_platforms.GET" um Plattformen zu finden.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Formats (ERFORDERLICH), z.B. "Story", "Post", "Reel", "Carousel".',
                ],
                'key' => [
                    'type' => 'string',
                    'description' => 'Eindeutiger Schlüssel pro Plattform (ERFORDERLICH), z.B. "story", "post", "reel". Lowercase, keine Leerzeichen.',
                ],
                'aspect_ratio' => [
                    'type' => 'string',
                    'description' => 'Optional: Seitenverhältnis, z.B. "9:16", "1:1", "16:9", "2:3".',
                ],
                'media_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Medientyp, z.B. "image", "video", "carousel".',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob das Format aktiv ist. Standard: true.',
                ],
            ],
            'required' => ['platform_id', 'name', 'key'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('create', BrandsSocialPlatformFormat::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Formate erstellen (Policy).');
            }

            // Plattform finden
            $platformId = $arguments['platform_id'] ?? null;
            if (!$platformId) {
                return ToolResult::error('VALIDATION_ERROR', 'platform_id ist erforderlich.');
            }

            $platform = BrandsSocialPlatform::find($platformId);
            if (!$platform) {
                return ToolResult::error('PLATFORM_NOT_FOUND', 'Die angegebene Plattform wurde nicht gefunden. Nutze "brands.social_platforms.GET" um Plattformen zu finden.');
            }

            // Name validieren
            $name = $arguments['name'] ?? null;
            if (!$name || !is_string($name) || trim($name) === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich und darf nicht leer sein.');
            }

            // Key validieren
            $key = $arguments['key'] ?? null;
            if (!$key || !is_string($key) || trim($key) === '') {
                return ToolResult::error('VALIDATION_ERROR', 'key ist erforderlich und darf nicht leer sein.');
            }

            $key = strtolower(trim($key));

            // Unique-Check (key pro Plattform)
            if (BrandsSocialPlatformFormat::where('platform_id', $platformId)->where('key', $key)->exists()) {
                return ToolResult::error('DUPLICATE_KEY', "Ein Format mit dem Key '{$key}' existiert bereits für die Plattform '{$platform->name}'.");
            }

            $format = BrandsSocialPlatformFormat::create([
                'platform_id' => $platformId,
                'name' => trim($name),
                'key' => $key,
                'aspect_ratio' => $arguments['aspect_ratio'] ?? null,
                'media_type' => $arguments['media_type'] ?? null,
                'is_active' => $arguments['is_active'] ?? true,
                'team_id' => $context->user->currentTeam?->id,
            ]);

            $format->load('platform');

            return ToolResult::success([
                'id' => $format->id,
                'platform_id' => $format->platform_id,
                'platform_name' => $format->platform->name,
                'name' => $format->name,
                'key' => $format->key,
                'aspect_ratio' => $format->aspect_ratio,
                'media_type' => $format->media_type,
                'is_active' => $format->is_active,
                'team_id' => $format->team_id,
                'created_at' => $format->created_at->toIso8601String(),
                'message' => "Format '{$format->name}' für Plattform '{$format->platform->name}' erfolgreich erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Formats: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'social', 'platform', 'format', 'create', 'lookup'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
