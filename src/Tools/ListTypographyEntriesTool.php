<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsTypographyBoard;
use Platform\Brands\Models\BrandsTypographyEntry;
use Illuminate\Support\Facades\Gate;

class ListTypographyEntriesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.typography_entries.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/typography_boards/{typography_board_id}/entries - Listet Typografie-Einträge eines Boards auf. REST-Parameter: typography_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'typography_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Typografie Boards. Nutze "brands.typography_boards.GET" um Boards zu finden.'
                    ],
                ]
            ]
        );
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

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Typografie Board.');
            }

            $query = BrandsTypographyEntry::query()
                ->where('typography_board_id', $boardId)
                ->with('typographyBoard');

            $this->applyStandardFilters($query, $arguments, [
                'name', 'role', 'font_family', 'font_source', 'font_weight', 'created_at', 'updated_at'
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'font_family', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'name', 'role', 'font_size', 'order', 'created_at', 'updated_at'
            ], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $entries = $query->get();

            $entriesList = $entries->map(function ($entry) {
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
                    'typography_board_id' => $entry->typography_board_id,
                    'created_at' => $entry->created_at->toIso8601String(),
                    'updated_at' => $entry->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'entries' => $entriesList,
                'count' => count($entriesList),
                'typography_board_id' => $boardId,
                'typography_board_name' => $board->name,
                'message' => count($entriesList) > 0
                    ? count($entriesList) . ' Typografie-Eintrag/-Einträge gefunden für Board "' . $board->name . '".'
                    : 'Keine Typografie-Einträge gefunden für Board "' . $board->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Typografie-Einträge: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'typography_entry', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
