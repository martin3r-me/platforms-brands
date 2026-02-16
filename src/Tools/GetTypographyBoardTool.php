<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsTypographyBoard;
use Illuminate\Support\Facades\Gate;

class GetTypographyBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.typography_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/typography_boards/{id} - Gibt ein einzelnes Typografie Board zurück inkl. aller Einträge. REST-Parameter: typography_board_id (required, integer) - Board-ID.';
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
            ],
            'required' => ['typography_board_id']
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

            $board = BrandsTypographyBoard::with(['brand', 'entries', 'user', 'team'])->find($boardId);
            if (!$board) {
                return ToolResult::error('TYPOGRAPHY_BOARD_NOT_FOUND', 'Das angegebene Typografie Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Typografie Board.');
            }

            $entries = $board->entries->map(function ($entry) {
                return [
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
                    'description' => $entry->description,
                ];
            })->toArray();

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'team_id' => $board->team_id,
                'done' => $board->done,
                'entries' => $entries,
                'entries_count' => count($entries),
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Typografie Board '{$board->name}' mit " . count($entries) . " Einträgen geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Typografie Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'typography_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
