<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Services\CtaAnalysisService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class AnalyzeCtasTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.ctas.ANALYZE';
    }

    public function getDescription(): string
    {
        return 'GET /brands/{brand_id}/ctas/analyze - CTA-Performance-Analyse mit verschiedenen Auswertungs-Typen. '
            . 'analysis_type bestimmt den Fokus: '
            . 'summary (Standard) = Übersicht aller CTAs mit Statistiken nach Typ und Funnel-Stage. '
            . 'top_performers = Beste CTAs nach Conversion Rate (min. 10 Impressions). '
            . 'weak_performers = Schwächste CTAs nach Conversion Rate (min. 10 Impressions). '
            . 'by_funnel_stage = Vergleich der Performance nach Funnel-Stage (awareness/consideration/decision). '
            . 'by_type = Vergleich der Performance nach CTA-Typ (primary/secondary/micro). '
            . 'inactive_high_potential = Inaktive CTAs mit guter Performance → Reaktivierungs-Kandidaten. '
            . 'no_tracking = Aktive CTAs ohne Tracking-Daten → Tracking-Integration prüfen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Brand (ERFORDERLICH). Nutze "brands.brands.GET" um Brands zu finden.',
                ],
                'analysis_type' => [
                    'type' => 'string',
                    'description' => 'Analyse-Typ: summary (Standard) | top_performers | weak_performers | by_funnel_stage | by_type | inactive_high_potential | no_tracking.',
                ],
            ],
            'required' => ['brand_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $brandId = $arguments['brand_id'] ?? null;
            if (!$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'brand_id ist erforderlich.');
            }

            $brand = BrandsBrand::find($brandId);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Brand wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Brand (Policy).');
            }

            $analysisService = app(CtaAnalysisService::class);
            $analysisType = $arguments['analysis_type'] ?? 'summary';

            $data = match ($analysisType) {
                'top_performers' => [
                    'type' => 'top_performers',
                    'analysis' => $analysisService->getTopPerformers($brand),
                ],
                'weak_performers' => [
                    'type' => 'weak_performers',
                    'analysis' => $analysisService->getWeakPerformers($brand),
                ],
                'by_funnel_stage' => [
                    'type' => 'by_funnel_stage',
                    'analysis' => $analysisService->getByFunnelStage($brand),
                ],
                'by_type' => [
                    'type' => 'by_type',
                    'analysis' => $analysisService->getByType($brand),
                ],
                'inactive_high_potential' => [
                    'type' => 'inactive_high_potential',
                    'analysis' => $analysisService->getInactiveHighPotential($brand),
                ],
                'no_tracking' => [
                    'type' => 'no_tracking',
                    'analysis' => $analysisService->getNoTrackingData($brand),
                ],
                default => [
                    'type' => 'summary',
                    'analysis' => $analysisService->getSummary($brand),
                ],
            };

            return ToolResult::success([
                'brand_id' => $brand->id,
                'brand_name' => $brand->name,
                ...$data,
                'message' => "CTA-Analyse ({$data['type']}) für '{$brand->name}' abgeschlossen.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler bei der CTA-Analyse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'cta', 'analyze', 'analytics'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
