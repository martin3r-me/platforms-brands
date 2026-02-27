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

class FetchSeoRankingsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keywords.FETCH_RANKINGS';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/keywords/fetch_rankings - Ruft SERP-Positionen für alle Keywords ab und erstellt Position-Snapshots. Prüft Budget-Limit vor dem Abruf. REST-Parameter: seo_board_id (required, integer).';
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
            ],
            'required' => ['seo_board_id']
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

            $seoBoard = BrandsSeoBoard::find($seoBoardId);
            if (!$seoBoard) {
                return ToolResult::error('SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Rankings für dieses SEO Board abrufen (Policy).');
            }

            $keywordService = app(SeoKeywordService::class);
            $result = $keywordService->fetchRankings($seoBoard, $context->user);

            if (isset($result['error'])) {
                return ToolResult::error('BUDGET_EXCEEDED', $result['error']);
            }

            return ToolResult::success([
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                'fetched' => $result['fetched'],
                'cost_cents' => $result['cost_cents'],
                'position_snapshots' => $result['position_snapshots'],
                'top_competitors' => $result['top_competitors'] ?? [],
                'message' => $result['fetched'] > 0
                    ? "{$result['fetched']} SERP-Rankings abgerufen. {$result['position_snapshots']} Position-Snapshot(s) gespeichert. Kosten: {$result['cost_cents']} Cents."
                    : 'Keine Rankings abgerufen (keine Keywords vorhanden).'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Abrufen der Rankings: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'fetch', 'rankings', 'serp', 'dataforseo'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['external_api', 'updates', 'costs'],
        ];
    }
}
