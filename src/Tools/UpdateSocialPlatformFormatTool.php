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
 * Tool zum Aktualisieren eines Social-Media-Plattform-Formats.
 */
class UpdateSocialPlatformFormatTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.social_platform_formats.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/social_platform_formats/{id} - Aktualisiert ein Social-Media-Plattform-Format. REST-Parameter: format_id (required), name (optional), key (optional, unique pro Plattform), aspect_ratio (optional), media_type (optional), output_schema (optional, JSON-Contract für Worker-Output), rules (optional, weiche Regeln als Key-Value-Pairs), is_active (optional). Worker-Workflow: 1) Format + output_schema laden, 2) Content gegen output_schema produzieren, 3) Ergebnis validieren. output_schema definiert den verbindlichen Output-Contract (Felder, Typen, Limits). rules enthält weiche Steuerungsregeln (z.B. allows_links, hashtag_style, tone_adjustment).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'format_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Formats (ERFORDERLICH). Nutze "brands.social_platform_formats.GET" um Formate zu finden.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name des Formats.',
                ],
                'key' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer eindeutiger Schlüssel (pro Plattform). Lowercase, keine Leerzeichen.',
                ],
                'aspect_ratio' => [
                    'type' => 'string',
                    'description' => 'Optional: Neues Seitenverhältnis, z.B. "9:16", "1:1". Null zum Entfernen.',
                ],
                'media_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Medientyp, z.B. "image", "video", "carousel". Null zum Entfernen.',
                ],
                'output_schema' => [
                    'type' => 'object',
                    'description' => 'Optional: JSON-Contract für Worker-Output. Definiert Felder, Typen und Limits, gegen die der Worker produziert. Beispiel: {"text": {"type": "string", "max_length": 2200, "required": true}, "image_url": {"type": "string", "required": true}}. Null zum Entfernen.',
                ],
                'rules' => [
                    'type' => 'object',
                    'description' => 'Optional: Weiche Regeln als Key-Value-Pairs für Feinsteuerung. Beispiel: {"allows_links": false, "hashtag_style": "many", "tone_adjustment": "casual"}. Null zum Entfernen.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob das Format aktiv ist.',
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
                Gate::forUser($context->user)->authorize('update', $format);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Format nicht bearbeiten (Policy).');
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

                // Unique-Check (key pro Plattform, nur wenn sich der Key ändert)
                if ($key !== $format->key) {
                    if (BrandsSocialPlatformFormat::where('platform_id', $format->platform_id)->where('key', $key)->exists()) {
                        return ToolResult::error('DUPLICATE_KEY', "Ein Format mit dem Key '{$key}' existiert bereits für diese Plattform.");
                    }
                }

                $updateData['key'] = $key;
            }

            if (array_key_exists('aspect_ratio', $arguments)) {
                $updateData['aspect_ratio'] = $arguments['aspect_ratio'];
            }

            if (array_key_exists('media_type', $arguments)) {
                $updateData['media_type'] = $arguments['media_type'];
            }

            if (array_key_exists('output_schema', $arguments)) {
                $value = $arguments['output_schema'];
                if ($value !== null && !is_array($value)) {
                    return ToolResult::error('VALIDATION_ERROR', 'output_schema muss ein JSON-Objekt oder null sein.');
                }
                $updateData['output_schema'] = $value;
            }

            if (array_key_exists('rules', $arguments)) {
                $value = $arguments['rules'];
                if ($value !== null && !is_array($value)) {
                    return ToolResult::error('VALIDATION_ERROR', 'rules muss ein JSON-Objekt oder null sein.');
                }
                $updateData['rules'] = $value;
            }

            if (isset($arguments['is_active'])) {
                $updateData['is_active'] = (bool) $arguments['is_active'];
            }

            if (!empty($updateData)) {
                $format->update($updateData);
            }

            $format->refresh();
            $format->load('platform');

            return ToolResult::success([
                'id' => $format->id,
                'platform_id' => $format->platform_id,
                'platform_name' => $format->platform->name,
                'name' => $format->name,
                'key' => $format->key,
                'aspect_ratio' => $format->aspect_ratio,
                'media_type' => $format->media_type,
                'output_schema' => $format->output_schema,
                'rules' => $format->rules,
                'is_active' => $format->is_active,
                'team_id' => $format->team_id,
                'updated_at' => $format->updated_at->toIso8601String(),
                'message' => "Format '{$format->name}' erfolgreich aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Formats: ' . $e->getMessage());
        }
    }
}
