<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen ContentBoards
 */
class GetContentBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_boards/{id} - Ruft ein einzelnes Content Board ab. REST-Parameter: id (required, integer) - Content Board-ID. Nutze "brands.content_boards.GET" um verfügbare Content Board-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Content Boards. Nutze "brands.content_boards.GET" um verfügbare Content Board-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Content Board-ID ist erforderlich. Nutze "brands.content_boards.GET" um Content Boards zu finden.');
            }

            // ContentBoard holen
            $contentBoard = BrandsContentBoard::with(['brand', 'user', 'team'])
                ->find($arguments['id']);

            if (!$contentBoard) {
                return ToolResult::error('CONTENT_BOARD_NOT_FOUND', 'Das angegebene Content Board wurde nicht gefunden. Nutze "brands.content_boards.GET" um alle verfügbaren Content Boards zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Content Board (Policy).');
            }

            $data = [
                'id' => $contentBoard->id,
                'uuid' => $contentBoard->uuid,
                'name' => $contentBoard->name,
                'description' => $contentBoard->description,
                'brand_id' => $contentBoard->brand_id,
                'brand_name' => $contentBoard->brand->name,
                'team_id' => $contentBoard->team_id,
                'user_id' => $contentBoard->user_id,
                'done' => $contentBoard->done,
                'done_at' => $contentBoard->done_at?->toIso8601String(),
                'created_at' => $contentBoard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Content Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
