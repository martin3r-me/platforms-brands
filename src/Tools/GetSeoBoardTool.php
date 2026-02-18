<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetSeoBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/seo_boards/{id} - Ruft ein einzelnes SEO Board mit allen Details ab. REST-Parameter: id (required, integer) - SEO Board-ID. Nutze "brands.seo_boards.GET" um verfügbare SEO Board-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des SEO Boards. Nutze "brands.seo_boards.GET" um verfügbare SEO Board-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'SEO Board-ID ist erforderlich. Nutze "brands.seo_boards.GET" um SEO Boards zu finden.');
            }

            $seoBoard = BrandsSeoBoard::with(['brand', 'user', 'team', 'keywordClusters', 'keywords'])
                ->find($arguments['id']);

            if (!$seoBoard) {
                return ToolResult::error('SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden. Nutze "brands.seo_boards.GET" um alle verfügbaren SEO Boards zu sehen.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses SEO Board (Policy).');
            }

            $data = [
                'id' => $seoBoard->id,
                'uuid' => $seoBoard->uuid,
                'name' => $seoBoard->name,
                'description' => $seoBoard->description,
                'brand_id' => $seoBoard->brand_id,
                'brand_name' => $seoBoard->brand->name,
                'team_id' => $seoBoard->team_id,
                'user_id' => $seoBoard->user_id,
                'done' => $seoBoard->done,
                'done_at' => $seoBoard->done_at?->toIso8601String(),
                'budget_limit_cents' => $seoBoard->budget_limit_cents,
                'budget_spent_cents' => $seoBoard->budget_spent_cents,
                'budget_remaining_cents' => $seoBoard->budget_remaining_cents,
                'budget_percentage' => $seoBoard->budget_percentage,
                'refresh_interval_days' => $seoBoard->refresh_interval_days,
                'last_refreshed_at' => $seoBoard->last_refreshed_at?->toIso8601String(),
                'clusters_count' => $seoBoard->keywordClusters->count(),
                'keywords_count' => $seoBoard->keywords->count(),
                'created_at' => $seoBoard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des SEO Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'seo_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
