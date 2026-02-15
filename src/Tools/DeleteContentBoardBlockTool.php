<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Löschen von ContentBoardBlocks im Brands-Modul
 */
class DeleteContentBoardBlockTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_board_blocks.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/content_board_blocks/{id} - Löscht einen Content Board Block. REST-Parameter: content_board_block_id (required, integer) - Content Board Block-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_board_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Content Board Blocks (ERFORDERLICH). Nutze "brands.content_board_blocks.GET" um Content Board Blocks zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass der Content Board Block wirklich gelöscht werden soll.'
                ]
            ],
            'required' => ['content_board_block_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'content_board_block_id',
                BrandsContentBoardBlock::class,
                'CONTENT_BOARD_BLOCK_NOT_FOUND',
                'Der angegebene Content Board Block wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $block = $validation['model'];
            $block->load('contentBoard');
            $contentBoard = $block->contentBoard;
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Content Board Block nicht löschen (Policy).');
            }

            $blockName = $block->name;
            $blockId = $block->id;
            $contentBoardId = $contentBoard->id;
            $teamId = $block->team_id;

            // ContentBoardBlock löschen
            $block->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.content_board_blocks.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'content_board_block_id' => $blockId,
                'content_board_block_name' => $blockName,
                'content_board_id' => $contentBoardId,
                'message' => "Content Board Block '{$blockName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Content Board Blocks: ' . $e->getMessage());
        }
    }
}
