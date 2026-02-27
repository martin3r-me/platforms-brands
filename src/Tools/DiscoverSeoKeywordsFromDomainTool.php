<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoKeywordService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DiscoverSeoKeywordsFromDomainTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keywords.DISCOVER_FROM_DOMAIN';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/keywords/discover_from_domain - Findet Keywords, für die eine Domain bei Google rankt. Gibt Ergebnisse zurück (importiert sie NICHT automatisch). REST-Parameter: seo_board_id (required, integer), domain (required, string), limit (optional, integer, default: 100).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Boards (ERFORDERLICH).'
                ],
                'domain' => [
                    'type' => 'string',
                    'description' => 'Domain, deren Rankings analysiert werden sollen (ERFORDERLICH). Beispiel: "example.com".'
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximale Anzahl an Keywords (Standard: 100).'
                ],
            ],
            'required' => ['seo_board_id', 'domain']
        ];
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

            $domain = $arguments['domain'] ?? null;
            if (!$domain) {
                return ToolResult::error('VALIDATION_ERROR', 'domain ist erforderlich.');
            }

            $seoBoard = BrandsSeoBoard::find($seoBoardId);
            if (!$seoBoard) {
                return ToolResult::error('SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Keywords für dieses SEO Board entdecken (Policy).');
            }

            $limit = $arguments['limit'] ?? 100;
            $keywordService = app(SeoKeywordService::class);
            $result = $keywordService->discoverFromDomain($seoBoard, $domain, $context->user, $limit);

            if (isset($result['error'])) {
                return ToolResult::error('BUDGET_EXCEEDED', $result['error']);
            }

            $keywordCount = count($result['keywords']);

            return ToolResult::success([
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                'domain' => $domain,
                'discovered_count' => $keywordCount,
                'cost_cents' => $result['cost_cents'],
                'keywords' => array_slice($result['keywords'], 0, 50),
                'message' => $keywordCount > 0
                    ? "{$keywordCount} Keywords für Domain '{$domain}' gefunden. Kosten: {$result['cost_cents']} Cents. Nutze 'brands.seo_keywords.POST' oder 'brands.seo_keywords.BULK_POST' um gewünschte Keywords hinzuzufügen."
                    : "Keine Keywords für Domain '{$domain}' gefunden."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler bei der Domain-Keyword-Analyse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'discover', 'domain', 'ranked', 'labs', 'dataforseo'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['external_api', 'costs'],
        ];
    }
}
