<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoKeywordService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;

class DiscoverSeoKeywordsFromCompetitorsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keywords.DISCOVER_FROM_COMPETITORS';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_boards/{seo_board_id}/keywords/discover_from_competitors - Zieht Keywords von allen Wettbewerber-Domains der zugehörigen Marke und importiert sie automatisch ins SEO Board. Nutzt die Competitor Boards der Brand, um Domains zu finden. REST-Parameter: seo_board_id (required, integer), limit_per_domain (optional, integer, default: 100).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Boards (ERFORDERLICH).',
                ],
                'limit_per_domain' => [
                    'type' => 'integer',
                    'description' => 'Maximale Anzahl Keywords pro Wettbewerber-Domain (Standard: 100).',
                ],
            ],
            'required' => ['seo_board_id'],
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Keywords für dieses SEO Board importieren (Policy).');
            }

            $limitPerDomain = $arguments['limit_per_domain'] ?? 100;
            $brand = $seoBoard->brand;

            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Das SEO Board ist keiner Marke zugeordnet.');
            }

            // Alle Competitors aus allen Competitor Boards der Brand laden
            $competitorBoards = $brand->competitorBoards()->with('competitors')->get();
            $competitors = $competitorBoards->flatMap(fn($board) => $board->competitors);

            // Filtern: nur mit website_url UND nicht is_own_brand
            $externalCompetitors = $competitors->filter(function ($competitor) {
                return !empty($competitor->website_url) && !$competitor->is_own_brand;
            });

            if ($externalCompetitors->isEmpty()) {
                return ToolResult::success([
                    'seo_board_id' => $seoBoard->id,
                    'seo_board_name' => $seoBoard->name,
                    'total_imported' => 0,
                    'total_cost_cents' => 0,
                    'per_domain' => [],
                    'message' => 'Keine externen Wettbewerber mit Website-URL gefunden. Bitte zuerst Wettbewerber mit website_url in einem Competitor Board anlegen.',
                ]);
            }

            // Existierende Keywords des Boards laden für Deduplizierung
            $existingKeywords = $seoBoard->keywords()
                ->pluck('keyword')
                ->map(fn($kw) => mb_strtolower($kw))
                ->flip()
                ->all();

            $keywordService = app(SeoKeywordService::class);
            $totalImported = 0;
            $totalCostCents = 0;
            $perDomain = [];
            $seenKeywords = $existingKeywords; // Start mit bestehenden Keywords

            foreach ($externalCompetitors as $competitor) {
                $host = parse_url($competitor->website_url, PHP_URL_HOST);
                if (!$host) {
                    continue;
                }

                $domain = preg_replace('/^www\./', '', $host);

                try {
                    $result = $keywordService->discoverFromDomain($seoBoard, $domain, $context->user, $limitPerDomain);

                    if (isset($result['error'])) {
                        $perDomain[] = [
                            'competitor' => $competitor->name,
                            'domain' => $domain,
                            'discovered' => 0,
                            'imported' => 0,
                            'duplicates_skipped' => 0,
                            'error' => $result['error'],
                        ];
                        continue;
                    }

                    $discovered = count($result['keywords']);
                    $imported = 0;
                    $duplicatesSkipped = 0;

                    foreach ($result['keywords'] as $kwData) {
                        $kwLower = mb_strtolower($kwData['keyword']);

                        if (isset($seenKeywords[$kwLower])) {
                            $duplicatesSkipped++;
                            continue;
                        }

                        $seenKeywords[$kwLower] = true;

                        $keywordService->addKeyword($seoBoard, [
                            'keyword' => $kwData['keyword'],
                            'search_volume' => $kwData['search_volume'] ?? null,
                            'keyword_difficulty' => $kwData['keyword_difficulty'] ?? null,
                            'cpc_cents' => isset($kwData['cpc']) ? (int) round($kwData['cpc'] * 100) : null,
                            'notes' => "Auto-imported from competitor: {$competitor->name} ({$domain})",
                        ], $context->user);

                        $imported++;
                    }

                    $totalImported += $imported;
                    $totalCostCents += $result['cost_cents'];

                    $perDomain[] = [
                        'competitor' => $competitor->name,
                        'domain' => $domain,
                        'discovered' => $discovered,
                        'imported' => $imported,
                        'duplicates_skipped' => $duplicatesSkipped,
                        'cost_cents' => $result['cost_cents'],
                    ];
                } catch (\Throwable $e) {
                    Log::warning('DiscoverSeoKeywordsFromCompetitors: Domain discovery failed', [
                        'competitor' => $competitor->name,
                        'domain' => $domain,
                        'error' => $e->getMessage(),
                    ]);

                    $perDomain[] = [
                        'competitor' => $competitor->name,
                        'domain' => $domain,
                        'discovered' => 0,
                        'imported' => 0,
                        'duplicates_skipped' => 0,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return ToolResult::success([
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                'brand' => $brand->name,
                'competitors_analyzed' => count($perDomain),
                'total_imported' => $totalImported,
                'total_cost_cents' => $totalCostCents,
                'per_domain' => $perDomain,
                'message' => $totalImported > 0
                    ? "{$totalImported} Keywords von " . count($perDomain) . " Wettbewerber-Domains importiert. Kosten: {$totalCostCents} Cents."
                    : 'Keine neuen Keywords importiert (alle bereits vorhanden oder keine Ergebnisse).',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler bei der Competitor-Keyword-Analyse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'discover', 'competitors', 'import', 'dataforseo'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['external_api', 'costs', 'creates'],
        ];
    }
}
