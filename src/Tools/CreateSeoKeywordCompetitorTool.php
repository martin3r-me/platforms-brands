<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Models\BrandsSeoKeywordCompetitor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateSeoKeywordCompetitorTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_competitors.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_keywords/{seo_keyword_id}/competitors - Erfasst ein Competitor-Ranking für ein Keyword. Dokumentiert welche Competitor-Domain für dieses Keyword rankt. REST-Parameter: seo_keyword_id (required, integer), domain (required, string), url (optional, string - die rankende URL), position (optional, integer - Competitor-Position in den SERPs).';
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
                    'description' => 'Competitor-Domain (ERFORDERLICH, z.B. "example.com").'
                ],
                'url' => [
                    'type' => 'string',
                    'description' => 'Optional: Die rankende URL des Competitors.'
                ],
                'position' => [
                    'type' => 'integer',
                    'description' => 'Optional: Ranking-Position des Competitors (z.B. 1-100).'
                ],
            ],
            'required' => ['seo_keyword_id', 'domain']
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

            $domain = $arguments['domain'] ?? null;
            if (empty($domain)) {
                return ToolResult::error('VALIDATION_ERROR', 'domain ist erforderlich.');
            }

            $seoKeyword = BrandsSeoKeyword::find($seoKeywordId);
            if (!$seoKeyword) {
                return ToolResult::error('SEO_KEYWORD_NOT_FOUND', 'Das angegebene SEO Keyword wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $seoKeyword);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Competitor-Rankings für dieses Keyword erstellen (Policy).');
            }

            $competitor = BrandsSeoKeywordCompetitor::create([
                'seo_keyword_id' => $seoKeywordId,
                'domain' => $domain,
                'url' => $arguments['url'] ?? null,
                'position' => $arguments['position'] ?? null,
                'tracked_at' => now(),
            ]);

            return ToolResult::success([
                'id' => $competitor->id,
                'uuid' => $competitor->uuid,
                'seo_keyword_id' => $seoKeyword->id,
                'keyword' => $seoKeyword->keyword,
                'domain' => $competitor->domain,
                'url' => $competitor->url,
                'position' => $competitor->position,
                'tracked_at' => $competitor->tracked_at->toIso8601String(),
                'competitor_gap' => $seoKeyword->fresh()->competitor_gap,
                'message' => "Competitor-Ranking für '{$seoKeyword->keyword}' erfasst: {$domain}"
                    . ($competitor->position ? " auf Position {$competitor->position}" : '')
                    . '.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Competitor-Rankings: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'competitor', 'ranking', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
