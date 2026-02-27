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

class FindSeoCompetitorsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_competitors.DISCOVER';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/competitors/discover - Findet Competitor-Domains für eine gegebene Domain über die DataForSEO Labs API. REST-Parameter: seo_board_id (required, integer), domain (required, string), limit (optional, integer, default: 20).';
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
                    'description' => 'Domain, deren Wettbewerber gesucht werden sollen (ERFORDERLICH). Beispiel: "example.com".'
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximale Anzahl an Competitor-Domains (Standard: 20).'
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Wettbewerber für dieses SEO Board suchen (Policy).');
            }

            $budgetGuard = app(SeoBudgetGuardService::class);
            $estimatedCost = (int) ceil(10); // ~$0.10/request
            if (!$budgetGuard->canFetch($seoBoard, $estimatedCost)) {
                return ToolResult::error('BUDGET_EXCEEDED', 'Budget limit exceeded');
            }

            $limit = $arguments['limit'] ?? 20;
            $api = app(DataForSeoApiService::class);

            $connectionId = $seoBoard->dataforseo_config['connection_id'] ?? null;
            if ($connectionId) {
                $api = $api->forConnection($connectionId);
            }

            $locationCode = $seoBoard->dataforseo_config['location_code'] ?? null;
            $languageCode = $seoBoard->dataforseo_config['language_code'] ?? null;

            $competitors = $api->getCompetitorsDomain($context->user, $domain, $locationCode, $languageCode, $limit);

            $competitorData = array_map(fn($c) => $c->toArray(), $competitors);

            $actualCost = $estimatedCost;
            $budgetGuard->recordCost($seoBoard, 'discover_competitors', count($competitorData), $actualCost, $context->user);

            return ToolResult::success([
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                'domain' => $domain,
                'competitors_count' => count($competitorData),
                'cost_cents' => $actualCost,
                'competitors' => $competitorData,
                'message' => count($competitorData) > 0
                    ? count($competitorData) . " Competitor-Domains für '{$domain}' gefunden. Kosten: {$actualCost} Cents."
                    : "Keine Competitor-Domains für '{$domain}' gefunden."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler bei der Wettbewerber-Analyse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_competitor', 'discover', 'domain', 'labs', 'dataforseo'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['external_api', 'costs'],
        ];
    }
}
