<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsTypographyEntry;
use Illuminate\Support\Facades\Gate;

class GetTypographyEntryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.typography_entry.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/typography_entries/{id} - Gibt einen einzelnen Typografie-Eintrag zurück. REST-Parameter: entry_id (required, integer) - Eintrag-ID.';
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
            ],
            'required' => ['entry_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $entryId = $arguments['entry_id'] ?? null;
            if (!$entryId) {
                return ToolResult::error('VALIDATION_ERROR', 'entry_id ist erforderlich.');
            }

            $entry = BrandsTypographyEntry::with('typographyBoard')->find($entryId);
            if (!$entry) {
                return ToolResult::error('ENTRY_NOT_FOUND', 'Der angegebene Typografie-Eintrag wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $entry)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Eintrag.');
            }

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'name' => $entry->name,
                'role' => $entry->role,
                'font_family' => $entry->font_family,
                'font_source' => $entry->font_source,
                'font_file_name' => $entry->font_file_name,
                'font_weight' => $entry->font_weight,
                'font_style' => $entry->font_style,
                'font_size' => $entry->font_size,
                'line_height' => $entry->line_height,
                'letter_spacing' => $entry->letter_spacing,
                'text_transform' => $entry->text_transform,
                'sample_text' => $entry->sample_text,
                'order' => $entry->order,
                'description' => $entry->description,
                'typography_board_id' => $entry->typography_board_id,
                'typography_board_name' => $entry->typographyBoard->name,
                'created_at' => $entry->created_at->toIso8601String(),
                'updated_at' => $entry->updated_at->toIso8601String(),
                'message' => "Typografie-Eintrag '{$entry->name}' geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Typografie-Eintrags: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'typography_entry', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
