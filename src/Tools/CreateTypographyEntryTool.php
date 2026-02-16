<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsTypographyBoard;
use Platform\Brands\Models\BrandsTypographyEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateTypographyEntryTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.typography_entries.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/typography_boards/{typography_board_id}/entries - Erstellt einen neuen Typografie-Eintrag (Schrift-Definition). REST-Parameter: typography_board_id (required), name (required), font_family (required), role (optional: h1-h6, body, caption etc.), font_weight (optional: 100-900), font_size (optional, px), line_height (optional), letter_spacing (optional, px).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'typography_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Typografie Boards (ERFORDERLICH). Nutze "brands.typography_boards.GET" um Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Eintrags (ERFORDERLICH), z.B. "Headline 1", "Body Text", "Caption".'
                ],
                'font_family' => [
                    'type' => 'string',
                    'description' => 'Schriftfamilie (ERFORDERLICH), z.B. "Inter", "Roboto", "Open Sans".'
                ],
                'role' => [
                    'type' => 'string',
                    'description' => 'Typografische Rolle: h1, h2, h3, h4, h5, h6, body, body-sm, caption, overline, subtitle.',
                    'enum' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'body', 'body-sm', 'caption', 'overline', 'subtitle']
                ],
                'font_source' => [
                    'type' => 'string',
                    'description' => 'Quelle der Schrift: system, google, custom. Standard: system.',
                    'enum' => ['system', 'google', 'custom']
                ],
                'font_weight' => [
                    'type' => 'integer',
                    'description' => 'Schriftgewicht (100-900). Standard: 400.'
                ],
                'font_style' => [
                    'type' => 'string',
                    'description' => 'Schriftstil: normal oder italic. Standard: normal.',
                    'enum' => ['normal', 'italic']
                ],
                'font_size' => [
                    'type' => 'number',
                    'description' => 'Schriftgröße in px. Standard: 16.'
                ],
                'line_height' => [
                    'type' => 'number',
                    'description' => 'Zeilenhöhe (z.B. 1.5 oder 24).'
                ],
                'letter_spacing' => [
                    'type' => 'number',
                    'description' => 'Buchstabenabstand in px.'
                ],
                'text_transform' => [
                    'type' => 'string',
                    'description' => 'Textumwandlung: uppercase, lowercase, capitalize.',
                    'enum' => ['uppercase', 'lowercase', 'capitalize']
                ],
                'sample_text' => [
                    'type' => 'string',
                    'description' => 'Beispieltext für die Vorschau.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung / Hinweise zur Verwendung.'
                ],
            ],
            'required' => ['typography_board_id', 'name', 'font_family']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $boardId = $arguments['typography_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'typography_board_id ist erforderlich.');
            }

            $board = BrandsTypographyBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('TYPOGRAPHY_BOARD_NOT_FOUND', 'Das angegebene Typografie Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Einträge für dieses Typografie Board erstellen.');
            }

            $name = $arguments['name'] ?? null;
            if (!$name) {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $fontFamily = $arguments['font_family'] ?? null;
            if (!$fontFamily) {
                return ToolResult::error('VALIDATION_ERROR', 'font_family ist erforderlich.');
            }

            $entry = BrandsTypographyEntry::create([
                'typography_board_id' => $board->id,
                'name' => $name,
                'role' => $arguments['role'] ?? null,
                'font_family' => $fontFamily,
                'font_source' => $arguments['font_source'] ?? 'system',
                'font_weight' => $arguments['font_weight'] ?? 400,
                'font_style' => $arguments['font_style'] ?? 'normal',
                'font_size' => $arguments['font_size'] ?? 16,
                'line_height' => $arguments['line_height'] ?? null,
                'letter_spacing' => $arguments['letter_spacing'] ?? null,
                'text_transform' => $arguments['text_transform'] ?? null,
                'sample_text' => $arguments['sample_text'] ?? null,
                'description' => $arguments['description'] ?? null,
            ]);

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
                'created_at' => $entry->created_at->toIso8601String(),
                'message' => "Typografie-Eintrag '{$entry->name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Typografie-Eintrags: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'typography_entry', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
