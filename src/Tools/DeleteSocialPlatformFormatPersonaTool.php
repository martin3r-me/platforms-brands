<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialPlatformFormat;
use Platform\Brands\Models\BrandsSocialPlatformFormatPersona;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Entfernen einer Persona-Verknüpfung von einem Platform-Format.
 */
class DeleteSocialPlatformFormatPersonaTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_platform_format_personas.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/social_platform_format_personas/{id} - Entfernt die Verknüpfung einer Persona von einem Platform-Format. '
            . 'REST-Parameter: platform_format_id (required, integer), persona_link_id (required, integer – ID der Verknüpfung).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'platform_format_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Platform-Formats (ERFORDERLICH).',
                ],
                'persona_link_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Persona-Verknüpfung (ERFORDERLICH). Nutze "brands.social_platform_format_personas.GET" um Verknüpfungs-IDs zu sehen.',
                ],
            ],
            'required' => ['platform_format_id', 'persona_link_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $platformFormatId = $arguments['platform_format_id'] ?? null;
            if (!$platformFormatId) {
                return ToolResult::error('VALIDATION_ERROR', 'platform_format_id ist erforderlich.');
            }

            $personaLinkId = $arguments['persona_link_id'] ?? null;
            if (!$personaLinkId) {
                return ToolResult::error('VALIDATION_ERROR', 'persona_link_id ist erforderlich.');
            }

            $format = BrandsSocialPlatformFormat::find($platformFormatId);
            if (!$format) {
                return ToolResult::error('FORMAT_NOT_FOUND', 'Das angegebene Platform-Format wurde nicht gefunden.');
            }

            // Policy: update auf Format prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $format);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Persona-Verknüpfungen für dieses Format entfernen (Policy).');
            }

            $link = BrandsSocialPlatformFormatPersona::where('id', $personaLinkId)
                ->where('platform_format_id', $platformFormatId)
                ->with('persona')
                ->first();

            if (!$link) {
                return ToolResult::error('LINK_NOT_FOUND', 'Die angegebene Persona-Verknüpfung wurde nicht gefunden oder gehört nicht zu diesem Format.');
            }

            $personaName = $link->persona->name;
            $personaId = $link->persona_id;

            $link->delete();

            return ToolResult::success([
                'platform_format_id' => $platformFormatId,
                'deleted_link_id' => $personaLinkId,
                'persona_id' => $personaId,
                'persona_name' => $personaName,
                'message' => "Persona-Verknüpfung entfernt: '{$personaName}' ist nicht mehr mit dem Format verknüpft.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Entfernen der Persona-Verknüpfung: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'social', 'platform', 'format', 'persona', 'audience', 'link', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => true,
            'side_effects' => ['deletes'],
        ];
    }
}
