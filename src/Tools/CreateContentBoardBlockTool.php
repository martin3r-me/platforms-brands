<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBoard;
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
        return 'POST /brands/content_boards/{content_board_id}/content_board_blocks - Erstellt einen neuen Content Board Block. REST-Parameter: content_board_id (required, integer) - Content Board-ID. name (optional, string) - Block-Name. description (optional, string) - Beschreibung. content_type (optional, string) - Content-Typ: "text", "image". Wenn "text" gewählt wird, wird automatisch ein leerer Text-Content erstellt.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Boards (ERFORDERLICH). Nutze "brands.content_boards.GET" um Content Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Blocks. Wenn nicht angegeben, wird automatisch "Neuer Block" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des Blocks (Inhalt/Text).'
                ],
                'content_type' => [
                    'type' => 'string',
                    'description' => 'Content-Typ des Blocks. Mögliche Werte: "text", "image". Wenn nicht angegeben, bleibt der Block ohne Content-Typ.',
                    'enum' => ['text', 'image']
                ],
            ],
            'required' => ['content_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Content Board finden
            $contentBoardId = $arguments['content_board_id'] ?? null;
            if (!$contentBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_board_id ist erforderlich.');
            }

            $contentBoard = BrandsContentBoard::find($contentBoardId);
            if (!$contentBoard) {
                return ToolResult::error('CONTENT_BOARD_NOT_FOUND', 'Das angegebene Content Board wurde nicht gefunden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Blocks für dieses Content Board erstellen (Policy).');
            }

            $name = $arguments['name'] ?? 'Neuer Block';
            $contentType = $arguments['content_type'] ?? null;

            // ContentBoardBlock direkt erstellen
            $block = BrandsContentBoardBlock::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'content_type' => $contentType,
                'content_id' => null,
                'user_id' => $context->user->id,
                'team_id' => $contentBoard->team_id,
                'content_board_id' => $contentBoard->id,
            ]);

            // Wenn content_type "text" ist, Text-Content erstellen
            if ($contentType === 'text') {
                $textContent = \Platform\Brands\Models\BrandsContentBoardBlockText::create([
                    'content' => '',
                    'user_id' => $context->user->id,
                    'team_id' => $contentBoard->team_id,
                ]);
                
                $block->content_type = 'text';
                $block->content_id = $textContent->id;
                $block->save();
            }

            $block->load(['contentBoard', 'user', 'team', 'content']);

            $result = [
                'id' => $block->id,
                'uuid' => $block->uuid,
                'name' => $block->name,
                'description' => $block->description,
                'content_type' => $block->content_type,
                'content_id' => $block->content_id,
                'content_board_id' => $contentBoard->id,
                'content_board_name' => $contentBoard->name,
                'team_id' => $block->team_id,
                'created_at' => $block->created_at->toIso8601String(),
                'message' => "Content Board Block '{$block->name}' erfolgreich erstellt."
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
