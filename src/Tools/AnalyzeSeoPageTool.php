<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoBudgetGuardService;
use Platform\Integrations\Services\DataForSeoApiService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class AnalyzeSeoPageTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_page.ANALYZE';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/page/analyze - Führt ein On-Page SEO Audit einer URL durch (Title, Description, Headings, Wortanzahl, Links, Ladezeit, On-Page Score). REST-Parameter: seo_board_id (required, integer), url (required, string).';
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
                'url' => [
                    'type' => 'string',
                    'description' => 'URL der Seite, die analysiert werden soll (ERFORDERLICH). Beispiel: "https://example.com/page".'
                ],
            ],
            'required' => ['seo_board_id', 'url']
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

            $url = $arguments['url'] ?? null;
            if (!$url) {
                return ToolResult::error('VALIDATION_ERROR', 'url ist erforderlich.');
            }

            $seoBoard = BrandsSeoBoard::find($seoBoardId);
            if (!$seoBoard) {
                return ToolResult::error('SEO_BOARD_NOT_FOUND', 'Das angegebene SEO Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Seiten-Analyse für dieses SEO Board durchführen (Policy).');
            }

            $budgetGuard = app(SeoBudgetGuardService::class);
            $estimatedCost = (int) ceil(15); // ~$0.15/page
            if (!$budgetGuard->canFetch($seoBoard, $estimatedCost)) {
                return ToolResult::error('BUDGET_EXCEEDED', 'Budget limit exceeded');
            }

            $api = app(DataForSeoApiService::class);

            $connectionId = $seoBoard->dataforseo_config['connection_id'] ?? null;
            if ($connectionId) {
                $api = $api->forConnection($connectionId);
            }

            $results = $api->getOnPageInstant($context->user, $url);

            if (empty($results)) {
                return ToolResult::success([
                    'seo_board_id' => $seoBoard->id,
                    'url' => $url,
                    'message' => 'Keine On-Page Daten für diese URL verfügbar.',
                ]);
            }

            $pageData = $results[0]->toArray();

            $actualCost = $estimatedCost;
            $budgetGuard->recordCost($seoBoard, 'on_page_analyze', 1, $actualCost, $context->user);

            return ToolResult::success([
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                'cost_cents' => $actualCost,
                'page_analysis' => $pageData,
                'message' => "On-Page Analyse für '{$url}' abgeschlossen. On-Page Score: " . ($pageData['onpage_score'] ?? 'N/A') . ". Kosten: {$actualCost} Cents."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler bei der On-Page Analyse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_page', 'analyze', 'on_page', 'audit', 'dataforseo'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['external_api', 'costs'],
        ];
    }
}
