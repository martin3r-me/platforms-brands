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
 * Tool zum Abrufen eines einzelnen ContentBoardBlocks
 */
class GetContentBoardBlockTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_board_block.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_board_blocks/{id} - Ruft einen einzelnen Content Board Block ab. REST-Parameter: id (required, integer) - Content Board Block-ID. Nutze "brands.content_board_blocks.GET" um verfügbare Content Board Block-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Content Board Blocks. Nutze "brands.content_board_blocks.GET" um verfügbare Content Board Block-IDs zu sehen.'
                ]
            ],
            'required' => ['id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Content Board Block-ID ist erforderlich. Nutze "brands.content_board_blocks.GET" um Content Board Blocks zu finden.');
            }

            // ContentBoardBlock holen
            $block = BrandsContentBoardBlock::with(['contentBoard', 'user', 'team'])
                ->find($arguments['id']);

            if (!$block) {
                return ToolResult::error('CONTENT_BOARD_BLOCK_NOT_FOUND', 'Der angegebene Content Board Block wurde nicht gefunden. Nutze "brands.content_board_blocks.GET" um alle verfügbaren Content Board Blocks zu sehen.');
            }

            $contentBoard = $block->contentBoard;

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Content Board Block (Policy).');
            }

            $block->load('content');
            
            $data = [
                'id' => $block->id,
                'uuid' => $block->uuid,
                'name' => $block->name,
                'description' => $block->description,
                'content_type' => $block->content_type,
                'content_id' => $block->content_id,
                'content_board_id' => $contentBoard->id,
                'content_board_name' => $contentBoard->name,
                'team_id' => $block->team_id,
                'user_id' => $block->user_id,
                'created_at' => $block->created_at->toIso8601String(),
            ];
            
            // Content-Daten hinzufügen, wenn vorhanden
            if ($block->content_type === 'text' && $block->content) {
                $data['content'] = [
                    'id' => $block->content->id,
                    'uuid' => $block->content->uuid,
                    'content' => $block->content->content,
                ];
            }

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Content Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_board_block', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
