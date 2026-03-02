<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefRanking;
use Platform\Brands\Services\ContentBriefRankingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class ListContentBriefRankingsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_rankings.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_brief_boards/{id}/rankings - Zeigt SERP-Ranking-Daten eines Content Briefs. Gibt aktuelle Keyword-Positionen, URL-Matches und historische Entwicklung zurück. Rankings werden wöchentlich automatisch getrackt (Sonntag). Parameter: content_brief_board_id (required), mode: "latest" (Standard) | "history" | "detail".';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Brief Boards (ERFORDERLICH).',
                ],
                'mode' => [
                    'type' => 'string',
                    'enum' => ['latest', 'history', 'detail'],
                    'description' => '"latest" = neueste Rankings pro Keyword (Standard). "history" = Verlauf über Zeit (Durchschnittswerte). "detail" = alle Datenpunkte für ein Keyword.',
                ],
                'seo_keyword_id' => [
                    'type' => 'integer',
                    'description' => 'Nur für mode="detail": ID des Keywords für Detailansicht.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Max. Einträge. Standard: 50.',
                ],
            ],
            'required' => ['content_brief_board_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $briefId = $arguments['content_brief_board_id'] ?? null;
            if (!$briefId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_brief_board_id ist erforderlich.');
            }

            $brief = BrandsContentBriefBoard::with('brand')->find($briefId);
            if (!$brief) {
                return ToolResult::error('NOT_FOUND', 'Content Brief Board nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $brief);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff auf dieses Content Brief Board.');
            }

            $mode = $arguments['mode'] ?? 'latest';
            $limit = min($arguments['limit'] ?? 50, 200);

            return match ($mode) {
                'history' => $this->historyMode($brief, $limit),
                'detail' => $this->detailMode($brief, $arguments['seo_keyword_id'] ?? null, $limit),
                default => $this->latestMode($brief, $limit),
            };
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Rankings: ' . $e->getMessage());
        }
    }

    protected function latestMode(BrandsContentBriefBoard $brief, int $limit): ToolResult
    {
        $latestDate = BrandsContentBriefRanking::where('content_brief_board_id', $brief->id)
            ->max('tracked_at');

        if (!$latestDate) {
            return ToolResult::success([
                'brief_id' => $brief->id,
                'brief_name' => $brief->name,
                'target_url' => $brief->target_url,
                'rankings' => [],
                'message' => 'Noch keine Rankings getrackt. Rankings werden wöchentlich automatisch erfasst.',
            ]);
        }

        $rankings = BrandsContentBriefRanking::where('content_brief_board_id', $brief->id)
            ->where('tracked_at', $latestDate)
            ->with('seoKeyword')
            ->orderByRaw('position IS NULL, position ASC') // rankt zuerst, dann nicht-gefundene
            ->limit($limit)
            ->get();

        $data = $rankings->map(function ($r) {
            return [
                'seo_keyword_id' => $r->seo_keyword_id,
                'keyword' => $r->seoKeyword->keyword,
                'search_volume' => $r->seoKeyword->search_volume,
                'position' => $r->position,
                'previous_position' => $r->previous_position,
                'delta' => $r->position_delta,
                'found_url' => $r->found_url,
                'is_target_match' => $r->is_target_match,
            ];
        })->toArray();

        $stats = [
            'total_keywords' => $rankings->count(),
            'ranking' => $rankings->whereNotNull('position')->count(),
            'not_found' => $rankings->whereNull('position')->count(),
            'target_matches' => $rankings->where('is_target_match', true)->count(),
            'avg_position' => $rankings->whereNotNull('position')->avg('position')
                ? round($rankings->whereNotNull('position')->avg('position'), 1)
                : null,
            'top_10' => $rankings->where('position', '<=', 10)->whereNotNull('position')->count(),
            'top_20' => $rankings->where('position', '<=', 20)->whereNotNull('position')->count(),
        ];

        return ToolResult::success([
            'brief_id' => $brief->id,
            'brief_name' => $brief->name,
            'target_url' => $brief->target_url,
            'tracked_at' => $latestDate,
            'stats' => $stats,
            'rankings' => $data,
        ]);
    }

    protected function historyMode(BrandsContentBriefBoard $brief, int $limit): ToolResult
    {
        $history = BrandsContentBriefRanking::where('content_brief_board_id', $brief->id)
            ->selectRaw('DATE(tracked_at) as date')
            ->selectRaw('COUNT(*) as keywords_tracked')
            ->selectRaw('AVG(CASE WHEN position IS NOT NULL THEN position END) as avg_position')
            ->selectRaw('SUM(CASE WHEN is_target_match = 1 THEN 1 ELSE 0 END) as matched_count')
            ->selectRaw('SUM(CASE WHEN position IS NULL THEN 1 ELSE 0 END) as not_found_count')
            ->selectRaw('SUM(CASE WHEN position IS NOT NULL AND position <= 10 THEN 1 ELSE 0 END) as top_10_count')
            ->selectRaw('SUM(CASE WHEN position IS NOT NULL AND position <= 20 THEN 1 ELSE 0 END) as top_20_count')
            ->selectRaw('SUM(cost_cents) as total_cost_cents')
            ->groupByRaw('DATE(tracked_at)')
            ->orderByDesc('date')
            ->limit($limit)
            ->get();

        $data = $history->map(function ($h) {
            return [
                'date' => $h->date,
                'keywords_tracked' => (int) $h->keywords_tracked,
                'avg_position' => $h->avg_position ? round((float) $h->avg_position, 1) : null,
                'matched_count' => (int) $h->matched_count,
                'not_found_count' => (int) $h->not_found_count,
                'top_10_count' => (int) $h->top_10_count,
                'top_20_count' => (int) $h->top_20_count,
                'cost_cents' => (int) $h->total_cost_cents,
            ];
        })->toArray();

        return ToolResult::success([
            'brief_id' => $brief->id,
            'brief_name' => $brief->name,
            'target_url' => $brief->target_url,
            'tracking_weeks' => count($data),
            'history' => $data,
        ]);
    }

    protected function detailMode(BrandsContentBriefBoard $brief, ?int $keywordId, int $limit): ToolResult
    {
        if (!$keywordId) {
            return ToolResult::error('VALIDATION_ERROR', 'seo_keyword_id ist für mode="detail" erforderlich.');
        }

        $rankings = BrandsContentBriefRanking::where('content_brief_board_id', $brief->id)
            ->where('seo_keyword_id', $keywordId)
            ->with('seoKeyword')
            ->orderByDesc('tracked_at')
            ->limit($limit)
            ->get();

        if ($rankings->isEmpty()) {
            return ToolResult::success([
                'brief_id' => $brief->id,
                'keyword_id' => $keywordId,
                'rankings' => [],
                'message' => 'Keine Ranking-Daten für dieses Keyword gefunden.',
            ]);
        }

        $keyword = $rankings->first()->seoKeyword;

        $data = $rankings->map(function ($r) {
            return [
                'tracked_at' => $r->tracked_at->toIso8601String(),
                'position' => $r->position,
                'previous_position' => $r->previous_position,
                'delta' => $r->position_delta,
                'found_url' => $r->found_url,
                'is_target_match' => $r->is_target_match,
                'serp_features' => $r->serp_features,
            ];
        })->toArray();

        return ToolResult::success([
            'brief_id' => $brief->id,
            'brief_name' => $brief->name,
            'keyword_id' => $keywordId,
            'keyword' => $keyword->keyword,
            'search_volume' => $keyword->search_volume,
            'data_points' => count($data),
            'rankings' => $data,
        ]);
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_brief', 'rankings', 'seo', 'tracking'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
