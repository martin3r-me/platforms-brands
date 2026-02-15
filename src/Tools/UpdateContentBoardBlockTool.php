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
        return 'PUT /brands/content_board_blocks/{id} - Aktualisiert einen Content Board Block. REST-Parameter: content_board_block_id (required, integer) - Content Board Block-ID. name (optional, string) - Name. description (optional, string) - Beschreibung (Inhalt/Text). content_type (optional, string) - Content-Typ ändern: "text", "image". Wenn "text" gewählt wird, wird automatisch ein leerer Text-Content erstellt (bestehender Content wird gelöscht).';
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
                'content_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Content-Typ des Blocks. Mögliche Werte: "text", "image". Wenn gesetzt, wird der Content-Typ geändert (bestehender Content wird gelöscht).',
                    'enum' => ['text', 'image']
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
            $block->load('contentBoard');
            $contentBoard = $block->contentBoard;
            
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

            // Content-Typ ändern
            if (isset($arguments['content_type'])) {
                $newContentType = $arguments['content_type'];
                
                // Wenn bereits ein Content existiert, löschen
                if ($block->content) {
                    $block->content->delete();
                }
                
                // Neuen Content erstellen, wenn Typ "text" ist
                if ($newContentType === 'text') {
                    $textContent = \Platform\Brands\Models\BrandsContentBoardBlockText::create([
                        'content' => '',
                        'user_id' => $context->user?->id ?? auth()->id(),
                        'team_id' => $contentBoard->team_id,
                    ]);
                    
                    $updateData['content_type'] = 'text';
                    $updateData['content_id'] = $textContent->id;
                } else {
                    $updateData['content_type'] = $newContentType;
                    $updateData['content_id'] = null;
                }
            }

            // ContentBoardBlock aktualisieren
            if (!empty($updateData)) {
                $block->update($updateData);
            }

            $block->refresh();
            $block->load(['contentBoard', 'user', 'team', 'content']);

            $result = [
                'content_board_block_id' => $block->id,
                'name' => $block->name,
                'description' => $block->description,
                'content_type' => $block->content_type,
                'content_id' => $block->content_id,
                'content_board_id' => $contentBoard->id,
                'content_board_name' => $contentBoard->name,
                'updated_at' => $block->updated_at->toIso8601String(),
                'message' => "Content Board Block '{$block->name}' erfolgreich aktualisiert."
            ];
            
            // Content-Daten hinzufügen, wenn vorhanden
            if ($block->content_type === 'text' && $block->content) {
                $result['text_content'] = [
                    'id' => $block->content->id,
                    'uuid' => $block->content->uuid,
                    'content' => $block->content->content,
                ];
            }

            return ToolResult::success($result);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Content Board Blocks: ' . $e->getMessage());
        }
    }
}
