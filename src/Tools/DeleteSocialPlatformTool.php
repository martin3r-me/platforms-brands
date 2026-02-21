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
 * Tool zum Löschen einer Social-Media-Plattform.
 * ACHTUNG: Beim Löschen werden auch alle zugehörigen Formate gelöscht (CASCADE).
 */
class DeleteSocialPlatformTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.social_platforms.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/social_platforms/{id} - Löscht eine Social-Media-Plattform und alle zugehörigen Formate (CASCADE). REST-Parameter: platform_id (required, integer) - Plattform-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'platform_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Plattform (ERFORDERLICH). Nutze "brands.social_platforms.GET" um Plattformen zu finden.',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass die Plattform wirklich gelöscht werden soll.',
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
                Gate::forUser($context->user)->authorize('delete', $platform);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Plattform nicht löschen (Policy).');
            }

            $platformName = $platform->name;
            $platformId = $platform->id;
            $formatsCount = $platform->formats()->count();

            // Plattform löschen (Formate werden per CASCADE gelöscht)
            $platform->delete();

            return ToolResult::success([
                'platform_id' => $platformId,
                'platform_name' => $platformName,
                'formats_deleted' => $formatsCount,
                'message' => "Plattform '{$platformName}' wurde erfolgreich gelöscht (inkl. {$formatsCount} Format(e)).",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Plattform: ' . $e->getMessage());
        }
    }
}
