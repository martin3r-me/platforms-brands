<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Models\BrandsSeoKeywordCompetitor;
use Illuminate\Support\Facades\Gate;

class ListSeoKeywordCompetitorsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_competitors.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/seo_keywords/{seo_keyword_id}/competitors - Listet Competitor-Rankings für ein Keyword auf. Zeigt welche Domains/URLs für dieses Keyword ranken. REST-Parameter: seo_keyword_id (required, integer). domain (optional, string - Filter nach Domain). limit (optional, Standard: 50).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_keyword_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Keywords (ERFORDERLICH).'
                ],
                'domain' => [
                    'type' => 'string',
                    'description' => 'Optional: Filter nach Competitor-Domain.'
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Optional: Maximale Anzahl der Ergebnisse. Standard: 50, Maximum: 500.'
                ],
            ],
            'required' => ['seo_keyword_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $seoKeywordId = $arguments['seo_keyword_id'] ?? null;
            if (!$seoKeywordId) {
                return ToolResult::error('VALIDATION_ERROR', 'seo_keyword_id ist erforderlich.');
            }

            $seoKeyword = BrandsSeoKeyword::find($seoKeywordId);
            if (!$seoKeyword) {
                return ToolResult::error('SEO_KEYWORD_NOT_FOUND', 'Das angegebene SEO Keyword wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $seoKeyword)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses SEO Keyword.');
            }

            $query = BrandsSeoKeywordCompetitor::query()
                ->where('seo_keyword_id', $seoKeywordId)
                ->orderByDesc('tracked_at');

            if (!empty($arguments['domain'])) {
                $query->where('domain', $arguments['domain']);
            }

            $limit = min($arguments['limit'] ?? 50, 500);
            $query->limit($limit);

            $competitors = $query->get();

            $competitorsList = $competitors->map(function ($comp) {
                return [
                    'id' => $comp->id,
                    'uuid' => $comp->uuid,
                    'domain' => $comp->domain,
                    'url' => $comp->url,
                    'position' => $comp->position,
                    'tracked_at' => $comp->tracked_at?->toIso8601String(),
                    'created_at' => $comp->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'seo_keyword_id' => $seoKeyword->id,
                'keyword' => $seoKeyword->keyword,
                'our_position' => $seoKeyword->position,
                'competitor_gap' => $seoKeyword->competitor_gap,
                'competitors' => $competitorsList,
                'count' => count($competitorsList),
                'message' => count($competitorsList) > 0
                    ? count($competitorsList) . ' Competitor-Ranking(s) für "' . $seoKeyword->keyword . '" gefunden.'
                    : 'Keine Competitor-Rankings für dieses Keyword vorhanden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Competitor-Rankings: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'seo_keyword', 'competitor', 'ranking', 'gap'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
