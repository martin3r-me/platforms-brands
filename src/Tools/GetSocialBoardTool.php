<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen SocialBoards
 */
class GetSocialBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.social_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/social_boards/{id} - Ruft ein einzelnes Social Board ab. REST-Parameter: id (required, integer) - Social Board-ID. Nutze "brands.social_boards.GET" um verfügbare Social Board-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Social Boards. Nutze "brands.social_boards.GET" um verfügbare Social Board-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Social Board-ID ist erforderlich. Nutze "brands.social_boards.GET" um Social Boards zu finden.');
            }

            // SocialBoard holen
            $socialBoard = BrandsSocialBoard::with(['brand', 'user', 'team', 'slots', 'cards'])
                ->find($arguments['id']);

            if (!$socialBoard) {
                return ToolResult::error('SOCIAL_BOARD_NOT_FOUND', 'Das angegebene Social Board wurde nicht gefunden. Nutze "brands.social_boards.GET" um alle verfügbaren Social Boards zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $socialBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Social Board (Policy).');
            }

            $data = [
                'id' => $socialBoard->id,
                'uuid' => $socialBoard->uuid,
                'name' => $socialBoard->name,
                'description' => $socialBoard->description,
                'brand_id' => $socialBoard->brand_id,
                'brand_name' => $socialBoard->brand->name,
                'team_id' => $socialBoard->team_id,
                'user_id' => $socialBoard->user_id,
                'done' => $socialBoard->done,
                'done_at' => $socialBoard->done_at?->toIso8601String(),
                'slots_count' => $socialBoard->slots->count(),
                'cards_count' => $socialBoard->cards->count(),
                'created_at' => $socialBoard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Social Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'social_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
