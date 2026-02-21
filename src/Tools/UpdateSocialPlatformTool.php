<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSocialPlatform;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Aktualisieren einer Social-Media-Plattform.
 */
class UpdateSocialPlatformTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.social_platforms.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/social_platforms/{id} - Aktualisiert eine Social-Media-Plattform. REST-Parameter: platform_id (required), name (optional), key (optional, unique), is_active (optional).';
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
                    'description' => 'Optional: Neuer Name der Plattform.',
                ],
                'key' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer eindeutiger Schlüssel. Lowercase, keine Leerzeichen.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob die Plattform aktiv ist.',
                ],
            ],
            'required' => ['platform_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'platform_id',
                BrandsSocialPlatform::class,
                'PLATFORM_NOT_FOUND',
                'Die angegebene Plattform wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $platform = $validation['model'];

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $platform);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Plattform nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['name'])) {
                $name = trim($arguments['name']);
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                }
                $updateData['name'] = $name;
            }

            if (isset($arguments['key'])) {
                $key = strtolower(trim($arguments['key']));
                if ($key === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'key darf nicht leer sein.');
                }

                // Unique-Check (nur wenn sich der Key ändert)
                if ($key !== $platform->key && BrandsSocialPlatform::where('key', $key)->exists()) {
                    return ToolResult::error('DUPLICATE_KEY', "Eine Plattform mit dem Key '{$key}' existiert bereits.");
                }

                $updateData['key'] = $key;
            }

            if (isset($arguments['is_active'])) {
                $updateData['is_active'] = (bool) $arguments['is_active'];
            }

            if (!empty($updateData)) {
                $platform->update($updateData);
            }

            $platform->refresh();

            return ToolResult::success([
                'id' => $platform->id,
                'name' => $platform->name,
                'key' => $platform->key,
                'is_active' => $platform->is_active,
                'team_id' => $platform->team_id,
                'updated_at' => $platform->updated_at->toIso8601String(),
                'message' => "Plattform '{$platform->name}' erfolgreich aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Plattform: ' . $e->getMessage());
        }
    }
}
