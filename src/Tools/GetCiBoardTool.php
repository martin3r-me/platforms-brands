<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsCiBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen CiBoards
 */
class GetCiBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.ci_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/ci_boards/{id} - Ruft ein einzelnes CI Board ab. REST-Parameter: id (required, integer) - CI Board-ID. Nutze "brands.ci_boards.GET" um verfügbare CI Board-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des CI Boards. Nutze "brands.ci_boards.GET" um verfügbare CI Board-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'CI Board-ID ist erforderlich. Nutze "brands.ci_boards.GET" um CI Boards zu finden.');
            }

            // CiBoard holen
            $ciBoard = BrandsCiBoard::with(['brand', 'user', 'team'])
                ->find($arguments['id']);

            if (!$ciBoard) {
                return ToolResult::error('CI_BOARD_NOT_FOUND', 'Das angegebene CI Board wurde nicht gefunden. Nutze "brands.ci_boards.GET" um alle verfügbaren CI Boards zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $ciBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses CI Board (Policy).');
            }

            $data = [
                'id' => $ciBoard->id,
                'uuid' => $ciBoard->uuid,
                'name' => $ciBoard->name,
                'description' => $ciBoard->description,
                'brand_id' => $ciBoard->brand_id,
                'brand_name' => $ciBoard->brand->name,
                'team_id' => $ciBoard->team_id,
                'user_id' => $ciBoard->user_id,
                'done' => $ciBoard->done,
                'done_at' => $ciBoard->done_at?->toIso8601String(),
                'created_at' => $ciBoard->created_at->toIso8601String(),
                'primary_color' => $ciBoard->primary_color,
                'secondary_color' => $ciBoard->secondary_color,
                'accent_color' => $ciBoard->accent_color,
                'slogan' => $ciBoard->slogan,
                'font_family' => $ciBoard->font_family,
                'tagline' => $ciBoard->tagline,
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des CI Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'ci_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
