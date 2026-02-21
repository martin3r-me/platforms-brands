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
 * Tool zum Erstellen einer neuen Social-Media-Plattform.
 *
 * Social Platforms sind eine lose Lookup-Tabelle — neue Plattformen
 * werden zur Laufzeit hinzugefügt ohne Code-Deployment.
 */
class CreateSocialPlatformTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_platforms.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/social_platforms - Erstellt eine neue Social-Media-Plattform. Social Platforms sind eine lose Lookup-Tabelle — neue Plattformen werden zur Laufzeit hinzugefügt ohne Code-Deployment. REST-Parameter: name (required), key (required, unique, z.B. "instagram"), is_active (optional, default true).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Plattform (ERFORDERLICH), z.B. "Instagram", "YouTube", "Snapchat".',
                ],
                'key' => [
                    'type' => 'string',
                    'description' => 'Eindeutiger Schlüssel (ERFORDERLICH), z.B. "instagram", "youtube", "snapchat". Wird für programmatische Referenzen verwendet. Lowercase, keine Leerzeichen.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob die Plattform aktiv ist. Standard: true.',
                ],
            ],
            'required' => ['name', 'key'],
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
                Gate::forUser($context->user)->authorize('create', BrandsSocialPlatform::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Plattformen erstellen (Policy).');
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

            // Unique-Check
            if (BrandsSocialPlatform::where('key', $key)->exists()) {
                return ToolResult::error('DUPLICATE_KEY', "Eine Plattform mit dem Key '{$key}' existiert bereits.");
            }

            $platform = BrandsSocialPlatform::create([
                'name' => trim($name),
                'key' => $key,
                'is_active' => $arguments['is_active'] ?? true,
                'team_id' => $context->user->currentTeam?->id,
            ]);

            return ToolResult::success([
                'id' => $platform->id,
                'name' => $platform->name,
                'key' => $platform->key,
                'is_active' => $platform->is_active,
                'team_id' => $platform->team_id,
                'created_at' => $platform->created_at->toIso8601String(),
                'message' => "Plattform '{$platform->name}' erfolgreich erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Plattform: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'social', 'platform', 'create', 'lookup'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
