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
        return 'GET /brands/seo_boards/{seo_board_id}/keywords/analyze - Analysiert Keywords: Zusammenfassung, Wettbewerber-Lücken, Content-Chancen, Persona-Mapping. REST-Parameter: seo_board_id (required, integer). analysis_type (optional, string) - summary|competitor_gap|content_opportunities|persona_mapping (Standard: summary).';
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
                    'description' => 'Optional: Analyse-Typ. summary (Standard) | competitor_gap | content_opportunities | persona_mapping.'
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

            $data = match ($analysisType) {
                'competitor_gap' => [
                    'type' => 'competitor_gap',
                    'analysis' => $analysisService->getCompetitorGapAnalysis($seoBoard),
                ],
                'content_opportunities' => [
                    'type' => 'content_opportunities',
                    'analysis' => $analysisService->getContentOpportunities($seoBoard),
                ],
                'persona_mapping' => [
                    'type' => 'persona_mapping',
                    'analysis' => $analysisService->getPersonaKeywordMapping($seoBoard),
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
