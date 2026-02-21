<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Models\BrandsSeoKeyword;
use Illuminate\Support\Facades\Gate;

class ListSeoKeywordsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.seo_keywords.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/seo_boards/{seo_board_id}/keywords - Listet Keywords eines SEO Boards auf. REST-Parameter: seo_board_id (required, integer). filters/search/sort/limit/offset (optional). Filterbar nach content_status (none|planned|draft|published|optimized) und location für Content-Pipeline-Übersicht und lokale SEO. Beispiel: filters=[{"field":"content_status","op":"eq","value":"planned"}] zeigt alle Keywords mit geplantem Content.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'seo_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des SEO Boards.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $seoBoardId = $arguments['seo_board_id'] ?? null;
            if (!$seoBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'seo_board_id ist erforderlich.');
            }

            $seoBoard = BrandsSeoBoard::find($seoBoardId);
            if (!$seoBoard) {
                return ToolResult::error('SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $seoBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses SEO Board.');
            }

            $query = BrandsSeoKeyword::query()
                ->where('seo_board_id', $seoBoardId)
                ->with(['seoBoard', 'cluster'])
                ->withCount('competitors');

            $this->applyStandardFilters($query, $arguments, [
                'keyword', 'search_intent', 'keyword_type', 'priority', 'keyword_cluster_id',
                'search_volume', 'keyword_difficulty', 'content_status', 'location',
                'created_at', 'updated_at'
            ]);
            $this->applyStandardSearch($query, $arguments, ['keyword', 'content_idea', 'notes', 'url', 'target_url', 'published_url', 'location']);
            $this->applyStandardSort($query, $arguments, [
                'keyword', 'search_volume', 'keyword_difficulty', 'cpc_cents', 'position', 'order', 'created_at'
            ], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $keywords = $query->get()->filter(function ($kw) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $kw);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            $keywordsList = $keywords->map(function ($kw) {
                return [
                    'id' => $kw->id,
                    'uuid' => $kw->uuid,
                    'keyword' => $kw->keyword,
                    'search_volume' => $kw->search_volume,
                    'keyword_difficulty' => $kw->keyword_difficulty,
                    'cpc_cents' => $kw->cpc_cents,
                    'trend' => $kw->trend,
                    'search_intent' => $kw->search_intent,
                    'keyword_type' => $kw->keyword_type,
                    'priority' => $kw->priority,
                    'url' => $kw->url,
                    'position' => $kw->position,
                    'keyword_cluster_id' => $kw->keyword_cluster_id,
                    'cluster_name' => $kw->cluster?->name,
                    'content_idea' => $kw->content_idea,
                    'content_status' => $kw->content_status,
                    'target_url' => $kw->target_url,
                    'published_url' => $kw->published_url,
                    'target_position' => $kw->target_position,
                    'location' => $kw->location,
                    'last_fetched_at' => $kw->last_fetched_at?->toIso8601String(),
                    'competitor_gap' => $kw->competitor_gap,
                    'competitors_count' => $kw->competitors_count ?? 0,
                    'created_at' => $kw->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'seo_keywords' => $keywordsList,
                'count' => count($keywordsList),
                'seo_board_id' => $seoBoardId,
                'seo_board_name' => $seoBoard->name,
                'message' => count($keywordsList) > 0
                    ? count($keywordsList) . ' Keyword(s) gefunden.'
                    : 'Keine Keywords gefunden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Keywords: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'seo_keyword', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
