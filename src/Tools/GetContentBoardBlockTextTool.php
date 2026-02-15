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
 * Tool zum Abrufen von Text-Content eines Content Board Blocks
 */
class GetContentBoardBlockTextTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_board_block_texts.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_board_blocks/{block_id}/texts - Ruft Text-Content eines Content Board Blocks ab. REST-Parameter: content_board_block_id (required, integer) - Block-ID.';
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
                Gate::forUser($context->user)->authorize('view', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Text-Content (Policy).');
            }

            // Prüfen, ob Block Text-Content hat
            if ($block->content_type !== 'text' || !$block->content) {
                return ToolResult::error('NO_TEXT_CONTENT', 'Dieser Block hat keinen Text-Content.');
            }

            $textContent = $block->content;

            return ToolResult::success([
                'content_board_block_id' => $block->id,
                'content_board_block_name' => $block->name,
                'text_content_id' => $textContent->id,
                'text_content_uuid' => $textContent->uuid,
                'content' => $textContent->content,
                'content_length' => strlen($textContent->content ?? ''),
                'word_count' => str_word_count($textContent->content ?? ''),
                'created_at' => $textContent->created_at->toIso8601String(),
                'updated_at' => $textContent->updated_at->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Text-Contents: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_board_block', 'text', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
