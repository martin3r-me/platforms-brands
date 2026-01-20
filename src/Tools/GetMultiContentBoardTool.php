<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsMultiContentBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen MultiContentBoards
 */
class GetMultiContentBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.multi_content_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/multi_content_boards/{id} - Ruft ein einzelnes Multi-Content-Board ab. REST-Parameter: id (required, integer) - Multi-Content-Board-ID. Nutze "brands.multi_content_boards.GET" um verfügbare Multi-Content-Board-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Multi-Content-Boards. Nutze "brands.multi_content_boards.GET" um verfügbare Multi-Content-Board-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Multi-Content-Board-ID ist erforderlich. Nutze "brands.multi_content_boards.GET" um Multi-Content-Boards zu finden.');
            }

            // MultiContentBoard holen
            $multiContentBoard = BrandsMultiContentBoard::with(['brand', 'user', 'team', 'slots'])
                ->find($arguments['id']);

            if (!$multiContentBoard) {
                return ToolResult::error('MULTI_CONTENT_BOARD_NOT_FOUND', 'Das angegebene Multi-Content-Board wurde nicht gefunden. Nutze "brands.multi_content_boards.GET" um alle verfügbaren Multi-Content-Boards zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $multiContentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Multi-Content-Board (Policy).');
            }

            $data = [
                'id' => $multiContentBoard->id,
                'uuid' => $multiContentBoard->uuid,
                'name' => $multiContentBoard->name,
                'description' => $multiContentBoard->description,
                'brand_id' => $multiContentBoard->brand_id,
                'brand_name' => $multiContentBoard->brand->name,
                'team_id' => $multiContentBoard->team_id,
                'user_id' => $multiContentBoard->user_id,
                'done' => $multiContentBoard->done,
                'done_at' => $multiContentBoard->done_at?->toIso8601String(),
                'slots_count' => $multiContentBoard->slots->count(),
                'created_at' => $multiContentBoard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Multi-Content-Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'multi_content_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
