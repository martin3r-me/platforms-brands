<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Platform\Brands\Models\BrandsContentBoardBlockText;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen von Text-Content für Content Board Blocks
 */
class CreateContentBoardBlockTextTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_board_block_texts.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_board_blocks/{block_id}/texts - Erstellt Text-Content für einen Content Board Block. REST-Parameter: content_board_block_id (required, integer) - Block-ID. content (optional, string) - Text-Inhalt (Markdown).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_board_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Board Blocks (ERFORDERLICH). Der Block muss bereits den Content-Typ "text" haben oder dieser wird automatisch gesetzt.'
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Text-Inhalt (Markdown). Wenn nicht angegeben, wird ein leerer Text erstellt.'
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

            $block = BrandsContentBoardBlock::with('row.section.contentBoard')->find($blockId);
            if (!$block) {
                return ToolResult::error('BLOCK_NOT_FOUND', 'Der angegebene Content Board Block wurde nicht gefunden.');
            }

            $contentBoard = $block->row->section->contentBoard;

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keinen Text-Content für diesen Block erstellen (Policy).');
            }

            // Wenn bereits Text-Content existiert, Fehler zurückgeben
            if ($block->content_type === 'text' && $block->content) {
                return ToolResult::error('CONTENT_EXISTS', 'Dieser Block hat bereits Text-Content. Nutze "brands.content_board_block_texts.PUT" um den Inhalt zu aktualisieren.');
            }

            // Text-Content erstellen
            $textContent = BrandsContentBoardBlockText::create([
                'content' => $arguments['content'] ?? '',
                'user_id' => $context->user->id,
                'team_id' => $contentBoard->team_id,
            ]);

            // Block mit Text-Content verknüpfen
            $block->content_type = 'text';
            $block->content_id = $textContent->id;
            $block->save();

            $block->refresh();
            $block->load('content');

            return ToolResult::success([
                'content_board_block_id' => $block->id,
                'text_content_id' => $textContent->id,
                'text_content_uuid' => $textContent->uuid,
                'content' => $textContent->content,
                'created_at' => $textContent->created_at->toIso8601String(),
                'message' => "Text-Content für Block '{$block->name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Text-Contents: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_board_block', 'text', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
