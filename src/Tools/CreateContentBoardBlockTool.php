<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBoardRow;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen von ContentBoardBlocks im Brands-Modul
 */
class CreateContentBoardBlockTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_board_blocks.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_board_rows/{row_id}/content_board_blocks - Erstellt einen neuen Content Board Block. REST-Parameter: row_id (required, integer) - Row-ID. name (optional, string) - Block-Name. description (optional, string) - Beschreibung. span (optional, integer) - Spaltenbreite (1-12).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'row_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Content Board Row (ERFORDERLICH). Nutze "brands.content_board.GET" um Rows zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Blocks. Wenn nicht angegeben, wird automatisch "Neuer Block" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des Blocks (Inhalt/Text).'
                ],
                'span' => [
                    'type' => 'integer',
                    'description' => 'Spaltenbreite des Blocks (1-12). Standard: 1.'
                ],
            ],
            'required' => ['row_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Row finden
            $rowId = $arguments['row_id'] ?? null;
            if (!$rowId) {
                return ToolResult::error('VALIDATION_ERROR', 'row_id ist erforderlich.');
            }

            $row = BrandsContentBoardRow::with('section.contentBoard')->find($rowId);
            if (!$row) {
                return ToolResult::error('ROW_NOT_FOUND', 'Die angegebene Row wurde nicht gefunden.');
            }

            $contentBoard = $row->section->contentBoard;

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Blocks für dieses Content Board erstellen (Policy).');
            }

            $name = $arguments['name'] ?? 'Neuer Block';
            $span = isset($arguments['span']) ? max(1, min(12, (int)$arguments['span'])) : 1;

            // ContentBoardBlock direkt erstellen
            $block = BrandsContentBoardBlock::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'span' => $span,
                'user_id' => $context->user->id,
                'team_id' => $contentBoard->team_id,
                'row_id' => $row->id,
            ]);

            $block->load(['row.section.contentBoard', 'user', 'team']);

            return ToolResult::success([
                'id' => $block->id,
                'uuid' => $block->uuid,
                'name' => $block->name,
                'description' => $block->description,
                'span' => $block->span,
                'row_id' => $block->row_id,
                'content_board_id' => $contentBoard->id,
                'content_board_name' => $contentBoard->name,
                'team_id' => $block->team_id,
                'created_at' => $block->created_at->toIso8601String(),
                'message' => "Content Board Block '{$block->name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Content Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_board_block', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
