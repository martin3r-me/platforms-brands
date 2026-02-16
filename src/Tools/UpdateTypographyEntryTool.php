<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsTypographyEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateTypographyEntryTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.typography_entries.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/typography_entries/{id} - Aktualisiert einen Typografie-Eintrag. REST-Parameter: entry_id (required), name, role, font_family, font_weight, font_size, line_height, letter_spacing, text_transform, sample_text, description (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entry_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Typografie-Eintrags (ERFORDERLICH). Nutze "brands.typography_entries.GET" um Einträge zu finden.'
                ],
                'name' => ['type' => 'string', 'description' => 'Optional: Name des Eintrags.'],
                'role' => [
                    'type' => 'string',
                    'description' => 'Optional: Typografische Rolle.',
                    'enum' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'body', 'body-sm', 'caption', 'overline', 'subtitle']
                ],
                'font_family' => ['type' => 'string', 'description' => 'Optional: Schriftfamilie.'],
                'font_source' => ['type' => 'string', 'description' => 'Optional: system, google, custom.', 'enum' => ['system', 'google', 'custom']],
                'font_weight' => ['type' => 'integer', 'description' => 'Optional: Schriftgewicht (100-900).'],
                'font_style' => ['type' => 'string', 'description' => 'Optional: normal oder italic.', 'enum' => ['normal', 'italic']],
                'font_size' => ['type' => 'number', 'description' => 'Optional: Schriftgröße in px.'],
                'line_height' => ['type' => 'number', 'description' => 'Optional: Zeilenhöhe.'],
                'letter_spacing' => ['type' => 'number', 'description' => 'Optional: Buchstabenabstand in px.'],
                'text_transform' => ['type' => 'string', 'description' => 'Optional: uppercase, lowercase, capitalize.'],
                'sample_text' => ['type' => 'string', 'description' => 'Optional: Beispieltext.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
            ],
            'required' => ['entry_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'entry_id',
                BrandsTypographyEntry::class,
                'ENTRY_NOT_FOUND',
                'Der angegebene Typografie-Eintrag wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $entry = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $entry);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Typografie-Eintrag nicht bearbeiten.');
            }

            $fields = ['name', 'role', 'font_family', 'font_source', 'font_weight', 'font_style', 'font_size', 'line_height', 'letter_spacing', 'text_transform', 'sample_text', 'description'];
            $updateData = [];

            foreach ($fields as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $entry->update($updateData);
            }

            $entry->refresh();
            $entry->load('typographyBoard');

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'name' => $entry->name,
                'role' => $entry->role,
                'font_family' => $entry->font_family,
                'font_source' => $entry->font_source,
                'font_weight' => $entry->font_weight,
                'font_style' => $entry->font_style,
                'font_size' => $entry->font_size,
                'line_height' => $entry->line_height,
                'letter_spacing' => $entry->letter_spacing,
                'text_transform' => $entry->text_transform,
                'sample_text' => $entry->sample_text,
                'order' => $entry->order,
                'typography_board_id' => $entry->typography_board_id,
                'updated_at' => $entry->updated_at->toIso8601String(),
                'message' => "Typografie-Eintrag '{$entry->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Typografie-Eintrags: ' . $e->getMessage());
        }
    }
}
