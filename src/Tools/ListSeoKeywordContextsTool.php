<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Models\BrandsSeoKeywordContext;
use Illuminate\Support\Facades\Gate;

class ListSeoKeywordContextsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_contexts.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/seo_keywords/{seo_keyword_id}/contexts - Zeigt alle verlinkten Kontexte eines SEO Keywords (lose Kopplung). '
            . 'Ein Keyword kann mit Content Board Blocks, Notes, URLs oder anderen Elementen verknüpft sein. '
            . 'REST-Parameter: seo_keyword_id (required, integer). context_type (optional, string – z.B. "content_board_block", "note", "url" zum Filtern). '
            . 'limit (optional, Standard: 50). '
            . 'Workflow-Beispiel: SEO Keyword "arbeitsmedizin köln" → content_status: planned → Content Board Artikel erstellen & verlinken → Content live → published_url fließt ins Keyword zurück → SEO Board zeigt verlinkten Content + Ranking.';
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
                'context_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Filter nach Kontext-Typ (z.B. "content_board_block", "note", "url").'
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Optional: Maximale Anzahl der Ergebnisse. Standard: 50, Maximum: 200.'
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

            $query = BrandsSeoKeywordContext::query()
                ->where('seo_keyword_id', $seoKeywordId)
                ->orderByDesc('created_at');

            if (!empty($arguments['context_type'])) {
                $query->where('context_type', $arguments['context_type']);
            }

            $limit = min($arguments['limit'] ?? 50, 200);
            $query->limit($limit);

            $contexts = $query->get();

            $contextsList = $contexts->map(function ($ctx) {
                return [
                    'id' => $ctx->id,
                    'uuid' => $ctx->uuid,
                    'context_type' => $ctx->context_type,
                    'context_id' => $ctx->context_id,
                    'label' => $ctx->label,
                    'url' => $ctx->url,
                    'meta' => $ctx->meta,
                    'created_at' => $ctx->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'seo_keyword_id' => $seoKeyword->id,
                'keyword' => $seoKeyword->keyword,
                'contexts' => $contextsList,
                'count' => count($contextsList),
                'message' => count($contextsList) > 0
                    ? count($contextsList) . ' Context-Link(s) für "' . $seoKeyword->keyword . '" gefunden.'
                    : 'Keine Context-Links für dieses Keyword vorhanden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Keyword-Kontexte: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'seo_keyword', 'context', 'link', 'content'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
