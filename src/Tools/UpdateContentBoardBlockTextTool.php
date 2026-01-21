<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Platform\Brands\Models\BrandsContentBoardBlockText;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Aktualisieren von Text-Content für Content Board Blocks
 */
class UpdateContentBoardBlockTextTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_board_block_texts.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/content_board_blocks/{block_id}/texts - Aktualisiert Text-Content eines Content Board Blocks. REST-Parameter: content_board_block_id (required, integer) - Block-ID. content (optional, string) - Text-Inhalt (Markdown).';
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
                'content' => [
                    'type' => 'string',
                    'description' => 'Text-Inhalt (Markdown). Wenn nicht angegeben, bleibt der Inhalt unverändert.'
                ],
            ],
            'required' => ['content_board_block_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $blockId = $arguments['content_board_block_id'] ?? null;
            if (!$blockId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_board_block_id ist erforderlich.');
            }

            $block = BrandsContentBoardBlock::with('row.section.contentBoard', 'content')->find($blockId);
            if (!$block) {
                return ToolResult::error('BLOCK_NOT_FOUND', 'Der angegebene Content Board Block wurde nicht gefunden.');
            }

            $contentBoard = $block->row->section->contentBoard;

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Text-Content nicht bearbeiten (Policy).');
            }

            // Prüfen, ob Block Text-Content hat
            if ($block->content_type !== 'text' || !$block->content) {
                return ToolResult::error('NO_TEXT_CONTENT', 'Dieser Block hat keinen Text-Content. Nutze "brands.content_board_block_texts.POST" um Text-Content zu erstellen.');
            }

            $textContent = $block->content;

            // Content aktualisieren
            if (isset($arguments['content'])) {
                $textContent->update([
                    'content' => $arguments['content'],
                ]);
            }

            $textContent->refresh();

            return ToolResult::success([
                'content_board_block_id' => $block->id,
                'text_content_id' => $textContent->id,
                'text_content_uuid' => $textContent->uuid,
                'content' => $textContent->content,
                'updated_at' => $textContent->updated_at->toIso8601String(),
                'message' => "Text-Content für Block '{$block->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Text-Contents: ' . $e->getMessage());
        }
    }
}
