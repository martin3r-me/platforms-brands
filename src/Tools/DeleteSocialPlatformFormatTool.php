<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSocialPlatformFormat;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen eines Social-Media-Plattform-Formats.
 */
class DeleteSocialPlatformFormatTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.social_platform_formats.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/social_platform_formats/{id} - Löscht ein Social-Media-Plattform-Format. REST-Parameter: format_id (required, integer) - Format-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'format_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Formats (ERFORDERLICH). Nutze "brands.social_platform_formats.GET" um Formate zu finden.',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass das Format wirklich gelöscht werden soll.',
                ],
            ],
            'required' => ['format_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'format_id',
                BrandsSocialPlatformFormat::class,
                'FORMAT_NOT_FOUND',
                'Das angegebene Format wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $format = $validation['model'];

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $format);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Format nicht löschen (Policy).');
            }

            $formatName = $format->name;
            $formatId = $format->id;
            $platformId = $format->platform_id;

            // Format löschen
            $format->delete();

            return ToolResult::success([
                'format_id' => $formatId,
                'format_name' => $formatName,
                'platform_id' => $platformId,
                'message' => "Format '{$formatName}' wurde erfolgreich gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Formats: ' . $e->getMessage());
        }
    }
}
