<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoKeyword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetSeoKeywordTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/seo_keywords/{id} - Ruft ein einzelnes SEO Keyword mit allen Details ab. REST-Parameter: id (required, integer) - Keyword-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Keywords.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Keyword-ID ist erforderlich.');
            }

            $keyword = BrandsSeoKeyword::with(['seoBoard', 'cluster', 'user', 'team'])
                ->find($arguments['id']);

            if (!$keyword) {
                return ToolResult::error('KEYWORD_NOT_FOUND', 'Das angegebene Keyword wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $keyword);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Keyword (Policy).');
            }

            return ToolResult::success([
                'id' => $keyword->id,
                'uuid' => $keyword->uuid,
                'keyword' => $keyword->keyword,
                'seo_board_id' => $keyword->seo_board_id,
                'seo_board_name' => $keyword->seoBoard->name,
                'keyword_cluster_id' => $keyword->keyword_cluster_id,
                'cluster_name' => $keyword->cluster?->name,
                'search_volume' => $keyword->search_volume,
                'keyword_difficulty' => $keyword->keyword_difficulty,
                'cpc_cents' => $keyword->cpc_cents,
                'trend' => $keyword->trend,
                'search_intent' => $keyword->search_intent,
                'keyword_type' => $keyword->keyword_type,
                'content_idea' => $keyword->content_idea,
                'priority' => $keyword->priority,
                'url' => $keyword->url,
                'position' => $keyword->position,
                'notes' => $keyword->notes,
                'last_fetched_at' => $keyword->last_fetched_at?->toIso8601String(),
                'team_id' => $keyword->team_id,
                'user_id' => $keyword->user_id,
                'created_at' => $keyword->created_at->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Keywords: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'seo_keyword', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
