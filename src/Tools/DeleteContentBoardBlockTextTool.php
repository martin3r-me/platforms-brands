<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen von Text-Content eines Content Board Blocks
 */
class DeleteContentBoardBlockTextTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_board_block_texts.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/content_board_blocks/{block_id}/texts - Löscht Text-Content eines Content Board Blocks. REST-Parameter: content_board_block_id (required, integer) - Block-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_board_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Board Blocks (ERFORDERLICH). Der Block muss den Content-Typ "text" haben.'
                ],
            ],
            'required' => ['content_board_block_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $blockId = $arguments['content_board_block_id'] ?? null;
            if (!$blockId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_board_block_id ist erforderlich.');
            }

            $block = BrandsContentBoardBlock::with('contentBoard', 'content')->find($blockId);
            if (!$block) {
                return ToolResult::error('BLOCK_NOT_FOUND', 'Der angegebene Content Board Block wurde nicht gefunden.');
            }

            $contentBoard = $block->contentBoard;

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Text-Content nicht löschen (Policy).');
            }

            // Prüfen, ob Block Text-Content hat
            if ($block->content_type !== 'text' || !$block->content) {
                return ToolResult::error('NO_TEXT_CONTENT', 'Dieser Block hat keinen Text-Content.');
            }

            $textContent = $block->content;
            $blockName = $block->name;

            // Text-Content löschen
            $textContent->delete();

            // Block Content-Typ zurücksetzen
            $block->content_type = null;
            $block->content_id = null;
            $block->save();

            return ToolResult::success([
                'content_board_block_id' => $block->id,
                'message' => "Text-Content für Block '{$blockName}' erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Text-Contents: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_board_block', 'text', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'destructive',
            'idempotent' => false,
            'side_effects' => ['deletes'],
        ];
    }
}
