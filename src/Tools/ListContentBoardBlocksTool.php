<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsContentBoard;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von ContentBoardBlocks im Brands-Modul
 */
class ListContentBoardBlocksTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.content_board_blocks.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_boards/{content_board_id}/content_board_blocks - Listet Content Board Blocks eines Content Boards auf. REST-Parameter: content_board_id (required, integer) - Content Board-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'content_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Content Boards. Nutze "brands.content_boards.GET" um Content Boards zu finden.'
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

            $contentBoardId = $arguments['content_board_id'] ?? null;
            if (!$contentBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_board_id ist erforderlich.');
            }

            $contentBoard = BrandsContentBoard::find($contentBoardId);
            if (!$contentBoard) {
                return ToolResult::error('CONTENT_BOARD_NOT_FOUND', 'Das angegebene Content Board wurde nicht gefunden.');
            }

            // Policy prüfen
            if (!Gate::forUser($context->user)->allows('view', $contentBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Content Board.');
            }
            
            // Query aufbauen - Content Board Blocks über Sections -> Rows
            $query = BrandsContentBoardBlock::query()
                ->whereHas('row.section', function($q) use ($contentBoardId) {
                    $q->where('content_board_id', $contentBoardId);
                })
                ->with(['row.section.contentBoard', 'user', 'team', 'content']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'created_at', 'updated_at'
            ]);
            
            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            
            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'name', 'created_at', 'updated_at', 'order'
            ], 'order', 'asc');
            
            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            // Blocks holen
            $blocks = $query->get();

            // Blocks formatieren
            $blocksList = $blocks->map(function($block) {
                $data = [
                    'id' => $block->id,
                    'uuid' => $block->uuid,
                    'name' => $block->name,
                    'description' => $block->description,
                    'span' => $block->span,
                    'content_type' => $block->content_type,
                    'content_id' => $block->content_id,
                    'row_id' => $block->row_id,
                    'content_board_id' => $block->row->section->content_board_id,
                    'content_board_name' => $block->row->section->contentBoard->name,
                    'team_id' => $block->team_id,
                    'user_id' => $block->user_id,
                    'created_at' => $block->created_at->toIso8601String(),
                ];
                
                // Content-Daten hinzufügen, wenn vorhanden
                if ($block->content_type === 'text' && $block->content) {
                    $data['text_content_preview'] = mb_substr($block->content->content ?? '', 0, 100);
                }
                
                return $data;
            })->values()->toArray();

            return ToolResult::success([
                'content_board_blocks' => $blocksList,
                'count' => count($blocksList),
                'content_board_id' => $contentBoardId,
                'content_board_name' => $contentBoard->name,
                'message' => count($blocksList) > 0 
                    ? count($blocksList) . ' Content Board Block(s) gefunden für Content Board "' . $contentBoard->name . '".'
                    : 'Keine Content Board Blocks gefunden für Content Board "' . $contentBoard->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Content Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_board_block', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
