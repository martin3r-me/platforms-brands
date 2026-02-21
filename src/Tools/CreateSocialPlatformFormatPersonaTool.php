<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialPlatformFormat;
use Platform\Brands\Models\BrandsPersona;
use Platform\Brands\Models\BrandsSocialPlatformFormatPersona;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Verknüpfen einer Persona mit einem Platform-Format.
 *
 * Personas liefern den Audience-Kontext für die Content-Produktion.
 * Der Worker merged: Platform Rules + Output-Schema + Personas = vollständiger Produktions-Kontext.
 */
class CreateSocialPlatformFormatPersonaTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_platform_format_personas.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/social_platform_format_personas - Verknüpft eine Persona mit einem Platform-Format. '
            . 'Personas liefern den Audience-Kontext: Ton, Wortwahl und Komplexität werden automatisch angepasst — ohne manuelles Briefing. '
            . 'Mehrere Personas pro Format möglich (z.B. TikTok Reel → "Gen Z" + "Early Adopter"). '
            . 'Worker-Workflow: 1) Format laden (output_schema + rules), 2) Verknüpfte Personas laden (dieses Tool), '
            . '3) Platform Rules + Output-Schema + Personas = vollständiger Produktions-Kontext, '
            . '4) Content gegen Schema produzieren, Ton an Personas anpassen. '
            . 'REST-Parameter: platform_format_id (required), persona_id (required), notes (optional, z.B. "Besonders auf kurze Sätze achten").';
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
                'persona_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Persona (ERFORDERLICH). Nutze "brands.personas.GET" um Persona-IDs zu sehen.',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Hinweise zur Persona-Anwendung für dieses Format, z.B. "Besonders auf kurze Sätze achten" oder "Formelle Ansprache verwenden".',
                ],
            ],
            'required' => ['platform_format_id', 'persona_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // platform_format_id validieren
            $platformFormatId = $arguments['platform_format_id'] ?? null;
            if (!$platformFormatId) {
                return ToolResult::error('VALIDATION_ERROR', 'platform_format_id ist erforderlich.');
            }

            $format = BrandsSocialPlatformFormat::with('platform')->find($platformFormatId);
            if (!$format) {
                return ToolResult::error('FORMAT_NOT_FOUND', 'Das angegebene Platform-Format wurde nicht gefunden. Nutze "brands.social_platform_formats.GET" um Formate zu finden.');
            }

            // Policy: update auf Format prüfen (Persona-Verknüpfung = Bearbeitung des Formats)
            try {
                Gate::forUser($context->user)->authorize('update', $format);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Persona-Verknüpfungen für dieses Format erstellen (Policy).');
            }

            // persona_id validieren
            $personaId = $arguments['persona_id'] ?? null;
            if (!$personaId) {
                return ToolResult::error('VALIDATION_ERROR', 'persona_id ist erforderlich.');
            }

            $persona = BrandsPersona::find($personaId);
            if (!$persona) {
                return ToolResult::error('PERSONA_NOT_FOUND', 'Die angegebene Persona wurde nicht gefunden. Nutze "brands.personas.GET" um Personas zu finden.');
            }

            // Duplikat-Check
            $existing = BrandsSocialPlatformFormatPersona::where('platform_format_id', $platformFormatId)
                ->where('persona_id', $personaId)
                ->first();

            if ($existing) {
                return ToolResult::error('DUPLICATE_LINK', "Diese Persona ist bereits mit dem Format verknüpft (ID: {$existing->id}).");
            }

            // Verknüpfung erstellen
            $link = BrandsSocialPlatformFormatPersona::create([
                'platform_format_id' => $platformFormatId,
                'persona_id' => $personaId,
                'notes' => $arguments['notes'] ?? null,
            ]);

            $link->load(['platformFormat.platform', 'persona']);

            return ToolResult::success([
                'id' => $link->id,
                'platform_format_id' => $link->platform_format_id,
                'format_name' => $link->platformFormat->name,
                'platform_name' => $link->platformFormat->platform->name,
                'persona_id' => $link->persona_id,
                'persona_name' => $link->persona->name,
                'notes' => $link->notes,
                'created_at' => $link->created_at->toIso8601String(),
                'message' => "Persona '{$link->persona->name}' mit Format '{$link->platformFormat->name}' ({$link->platformFormat->platform->name}) verknüpft."
                    . ($link->notes ? " Hinweis: {$link->notes}" : ''),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Verknüpfen der Persona: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'social', 'platform', 'format', 'persona', 'audience', 'link', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
