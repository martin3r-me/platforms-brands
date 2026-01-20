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
 * Tool zum Bearbeiten von ContentBoardBlocks
 */
class UpdateContentBoardBlockTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_board_blocks.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/content_board_blocks/{id} - Aktualisiert einen Content Board Block. REST-Parameter: content_board_block_id (required, integer) - Content Board Block-ID. name (optional, string) - Name. description (optional, string) - Beschreibung (Inhalt/Text). span (optional, integer) - Spaltenbreite (1-12).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_board_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Board Blocks (ERFORDERLICH). Nutze "brands.content_board_blocks.GET" um Content Board Blocks zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des Blocks.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Blocks (Inhalt/Text).'
                ],
                'span' => [
                    'type' => 'integer',
                    'description' => 'Optional: Spaltenbreite des Blocks (1-12).'
                ],
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
            $block->load('row.section.contentBoard');
            $contentBoard = $block->row->section->contentBoard;
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Content Board Block nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            if (isset($arguments['span'])) {
                $updateData['span'] = max(1, min(12, (int)$arguments['span']));
            }

            // ContentBoardBlock aktualisieren
            if (!empty($updateData)) {
                $block->update($updateData);
            }

            $block->refresh();
            $block->load(['row.section.contentBoard', 'user', 'team']);

            return ToolResult::success([
                'content_board_block_id' => $block->id,
                'name' => $block->name,
                'description' => $block->description,
                'span' => $block->span,
                'content_board_id' => $contentBoard->id,
                'content_board_name' => $contentBoard->name,
                'updated_at' => $block->updated_at->toIso8601String(),
                'message' => "Content Board Block '{$block->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Content Board Blocks: ' . $e->getMessage());
        }
    }
}
