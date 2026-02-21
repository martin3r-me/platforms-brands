<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialPlatformFormat;
use Platform\Brands\Models\BrandsSocialPlatformFormatPersona;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten der Personas eines Platform-Formats.
 *
 * Zeigt alle verknüpften Personas (Audience-Kontext) eines Formats.
 * Worker-Kontext: Schema + Rules + Personas = Produktions-Briefing.
 */
class ListSocialPlatformFormatPersonasTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_platform_format_personas.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/social_platform_format_personas - Listet alle verknüpften Personas eines Platform-Formats auf. '
            . 'Personas liefern den Audience-Kontext für die Content-Produktion. '
            . 'Worker-Kontext: Output-Schema (Felder, Typen, Limits) + Rules (Feinsteuerung) + Personas (Zielgruppe) = vollständiges Produktions-Briefing. '
            . 'REST-Parameter: platform_format_id (required, integer). '
            . 'Tipp: Nutze "brands.social_platform_formats.GET" um Formate inkl. Personas in einer Abfrage zu laden.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'platform_format_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Platform-Formats (ERFORDERLICH). Nutze "brands.social_platform_formats.GET" um Format-IDs zu sehen.',
                ],
            ],
            'required' => ['platform_format_id'],
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

            $format = BrandsSocialPlatformFormat::with('platform')->find($platformFormatId);
            if (!$format) {
                return ToolResult::error('FORMAT_NOT_FOUND', 'Das angegebene Platform-Format wurde nicht gefunden. Nutze "brands.social_platform_formats.GET" um Formate zu finden.');
            }

            // Policy: view auf Format prüfen
            if (!Gate::forUser($context->user)->allows('view', $format)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Format.');
            }

            $links = BrandsSocialPlatformFormatPersona::query()
                ->where('platform_format_id', $platformFormatId)
                ->with(['persona.personaBoard'])
                ->orderBy('created_at', 'asc')
                ->get();

            $personasList = $links->map(function ($link) {
                return [
                    'id' => $link->id,
                    'persona_id' => $link->persona_id,
                    'persona_name' => $link->persona->name,
                    'persona_age' => $link->persona->age,
                    'persona_gender' => $link->persona->gender,
                    'persona_occupation' => $link->persona->occupation,
                    'persona_bio' => $link->persona->bio,
                    'persona_board_id' => $link->persona->persona_board_id,
                    'persona_board_name' => $link->persona->personaBoard?->name,
                    'notes' => $link->notes,
                    'created_at' => $link->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'platform_format_id' => $format->id,
                'format_name' => $format->name,
                'platform_name' => $format->platform->name,
                'personas' => $personasList,
                'count' => count($personasList),
                'message' => count($personasList) > 0
                    ? count($personasList) . " Persona(s) für Format '{$format->name}' ({$format->platform->name}) gefunden."
                    : "Keine Personas für Format '{$format->name}' verknüpft.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Format-Personas: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'social', 'platform', 'format', 'persona', 'audience', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
