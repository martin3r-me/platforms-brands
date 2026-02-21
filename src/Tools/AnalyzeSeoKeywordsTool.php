<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoAnalysisService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class AnalyzeSeoKeywordsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keywords.ANALYZE';
    }

    public function getDescription(): string
    {
        return 'GET /brands/seo_boards/{seo_board_id}/keywords/analyze - Strategische Keyword-Analyse mit actionable Empfehlungen. '
            . 'analysis_type bestimmt den Fokus: '
            . 'summary (Standard) = Übersicht aller Keywords mit Statistiken. '
            . 'quick_wins = Keywords mit hohem Suchvolumen + niedriger Difficulty + kein Content → schnelle Rankings erzielen. '
            . 'content_gaps = Keywords ohne Content (none/planned), gruppiert nach Cluster → Content-Lücken aufdecken. '
            . 'declining = Keywords die >5 Positionen verloren haben → Content optimieren bevor Rankings weiter fallen. '
            . 'defend = Keywords in Position 1-3 mit hohem Suchvolumen → Top-Rankings schützen. '
            . 'cluster_health = Pro Cluster: Coverage Score, Ø Position, Content-Status-Verteilung → Themen-Abdeckung bewerten. '
            . 'local_opportunities = Keywords mit Ortsbezug ohne Content → lokale Chancen nutzen. '
            . 'competitor_gap|competitor_gaps|content_opportunities|persona_mapping|ranking_trends = bestehende Analysen. '
            . 'days (optional, für declining/ranking_trends: Zeitraum in Tagen, Standard: 30).';
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
                'analysis_type' => [
                    'type' => 'string',
                    'description' => 'Analyse-Typ: summary (Standard) | quick_wins | content_gaps | declining | defend | cluster_health | local_opportunities | competitor_gap | competitor_gaps | content_opportunities | persona_mapping | ranking_trends.'
                ],
                'days' => [
                    'type' => 'integer',
                    'description' => 'Optional: Zeitraum in Tagen für declining und ranking_trends Analyse. Standard: 30.'
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
                Gate::forUser($context->user)->authorize('view', $seoBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses SEO Board (Policy).');
            }

            $analysisService = app(SeoAnalysisService::class);
            $analysisType = $arguments['analysis_type'] ?? 'summary';

            $days = (int) ($arguments['days'] ?? 30);

            $data = match ($analysisType) {
                'quick_wins' => [
                    'type' => 'quick_wins',
                    'analysis' => $analysisService->getQuickWins($seoBoard),
                ],
                'content_gaps' => [
                    'type' => 'content_gaps',
                    'analysis' => $analysisService->getContentGaps($seoBoard),
                ],
                'declining' => [
                    'type' => 'declining',
                    'analysis' => $analysisService->getDeclining($seoBoard, $days),
                ],
                'defend' => [
                    'type' => 'defend',
                    'analysis' => $analysisService->getDefend($seoBoard),
                ],
                'cluster_health' => [
                    'type' => 'cluster_health',
                    'analysis' => $analysisService->getClusterHealth($seoBoard),
                ],
                'local_opportunities' => [
                    'type' => 'local_opportunities',
                    'analysis' => $analysisService->getLocalOpportunities($seoBoard),
                ],
                'competitor_gap' => [
                    'type' => 'competitor_gap',
                    'analysis' => $analysisService->getCompetitorGapAnalysis($seoBoard),
                ],
                'competitor_gaps' => [
                    'type' => 'competitor_gaps',
                    'analysis' => $analysisService->getCompetitorGaps($seoBoard),
                ],
                'content_opportunities' => [
                    'type' => 'content_opportunities',
                    'analysis' => $analysisService->getContentOpportunities($seoBoard),
                ],
                'persona_mapping' => [
                    'type' => 'persona_mapping',
                    'analysis' => $analysisService->getPersonaKeywordMapping($seoBoard),
                ],
                'ranking_trends' => [
                    'type' => 'ranking_trends',
                    'analysis' => $analysisService->getRankingTrends($seoBoard, $days),
                ],
                default => [
                    'type' => 'summary',
                    'analysis' => $analysisService->getKeywordSummary($seoBoard),
                ],
            };

            return ToolResult::success([
                'seo_board_id' => $seoBoard->id,
                'seo_board_name' => $seoBoard->name,
                ...$data,
                'message' => "Keyword-Analyse ({$data['type']}) für '{$seoBoard->name}' abgeschlossen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler bei der Keyword-Analyse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'seo_keyword', 'analyze', 'analysis'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
