<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCta;

class CtaAnalysisService
{
    /**
     * Summary: Overview of all CTAs with aggregate statistics.
     */
    public function getSummary(BrandsBrand $brand): array
    {
        $ctas = $brand->ctas()->get();

        $totalImpressions = $ctas->sum('impressions');
        $totalClicks = $ctas->sum('clicks');
        $avgConversionRate = $totalImpressions > 0
            ? round($totalClicks / $totalImpressions, 4)
            : 0;

        return [
            'total_ctas' => $ctas->count(),
            'active_ctas' => $ctas->where('is_active', true)->count(),
            'inactive_ctas' => $ctas->where('is_active', false)->count(),
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'avg_conversion_rate' => $avgConversionRate,
            'by_type' => $ctas->groupBy('type')->map(fn ($group) => [
                'count' => $group->count(),
                'impressions' => $group->sum('impressions'),
                'clicks' => $group->sum('clicks'),
                'conversion_rate' => $group->sum('impressions') > 0
                    ? round($group->sum('clicks') / $group->sum('impressions'), 4)
                    : 0,
            ])->toArray(),
            'by_funnel_stage' => $ctas->groupBy('funnel_stage')->map(fn ($group) => [
                'count' => $group->count(),
                'impressions' => $group->sum('impressions'),
                'clicks' => $group->sum('clicks'),
                'conversion_rate' => $group->sum('impressions') > 0
                    ? round($group->sum('clicks') / $group->sum('impressions'), 4)
                    : 0,
            ])->toArray(),
        ];
    }

    /**
     * Top performers: CTAs with highest conversion rate (min. 10 impressions).
     */
    public function getTopPerformers(BrandsBrand $brand): array
    {
        $ctas = $brand->ctas()
            ->with(['targetPage.contentBoard'])
            ->where('impressions', '>=', 10)
            ->get()
            ->sortByDesc('conversion_rate')
            ->take(10);

        return $ctas->map(fn ($cta) => [
            'id' => $cta->id,
            'label' => $cta->label,
            'type' => $cta->type,
            'funnel_stage' => $cta->funnel_stage,
            'impressions' => $cta->impressions,
            'clicks' => $cta->clicks,
            'conversion_rate' => $cta->conversion_rate,
            'page_context_url' => $cta->getPageContextUrl(),
            'last_clicked_at' => $cta->last_clicked_at?->toIso8601String(),
        ])->values()->toArray();
    }

    /**
     * Weak performers: CTAs with lowest conversion rate (min. 10 impressions).
     */
    public function getWeakPerformers(BrandsBrand $brand): array
    {
        $ctas = $brand->ctas()
            ->with(['targetPage.contentBoard'])
            ->where('impressions', '>=', 10)
            ->get()
            ->sortBy('conversion_rate')
            ->take(10);

        return $ctas->map(fn ($cta) => [
            'id' => $cta->id,
            'label' => $cta->label,
            'type' => $cta->type,
            'funnel_stage' => $cta->funnel_stage,
            'impressions' => $cta->impressions,
            'clicks' => $cta->clicks,
            'conversion_rate' => $cta->conversion_rate,
            'page_context_url' => $cta->getPageContextUrl(),
            'last_clicked_at' => $cta->last_clicked_at?->toIso8601String(),
        ])->values()->toArray();
    }

    /**
     * Comparison by funnel stage: Aggregate metrics per funnel stage.
     */
    public function getByFunnelStage(BrandsBrand $brand): array
    {
        $ctas = $brand->ctas()->get();

        $stages = [];
        foreach (BrandsCta::FUNNEL_STAGES as $stage) {
            $group = $ctas->where('funnel_stage', $stage);
            $impressions = $group->sum('impressions');
            $clicks = $group->sum('clicks');

            $stages[$stage] = [
                'count' => $group->count(),
                'active' => $group->where('is_active', true)->count(),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'conversion_rate' => $impressions > 0
                    ? round($clicks / $impressions, 4)
                    : 0,
                'top_cta' => $group->sortByDesc('conversion_rate')->first()?->only([
                    'id', 'label', 'conversion_rate',
                ]),
            ];
        }

        return $stages;
    }

    /**
     * Comparison by type: Aggregate metrics per CTA type.
     */
    public function getByType(BrandsBrand $brand): array
    {
        $ctas = $brand->ctas()->get();

        $types = [];
        foreach (BrandsCta::TYPES as $type) {
            $group = $ctas->where('type', $type);
            $impressions = $group->sum('impressions');
            $clicks = $group->sum('clicks');

            $types[$type] = [
                'count' => $group->count(),
                'active' => $group->where('is_active', true)->count(),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'conversion_rate' => $impressions > 0
                    ? round($clicks / $impressions, 4)
                    : 0,
                'top_cta' => $group->sortByDesc('conversion_rate')->first()?->only([
                    'id', 'label', 'conversion_rate',
                ]),
            ];
        }

        return $types;
    }

    /**
     * Inactive high-potential: Inactive CTAs that had good performance.
     */
    public function getInactiveHighPotential(BrandsBrand $brand): array
    {
        $ctas = $brand->ctas()
            ->where('is_active', false)
            ->where('clicks', '>', 0)
            ->get()
            ->sortByDesc('conversion_rate')
            ->take(10);

        return $ctas->map(fn ($cta) => [
            'id' => $cta->id,
            'label' => $cta->label,
            'type' => $cta->type,
            'funnel_stage' => $cta->funnel_stage,
            'impressions' => $cta->impressions,
            'clicks' => $cta->clicks,
            'conversion_rate' => $cta->conversion_rate,
        ])->values()->toArray();
    }

    /**
     * No tracking data: Active CTAs with zero impressions.
     */
    public function getNoTrackingData(BrandsBrand $brand): array
    {
        $ctas = $brand->ctas()
            ->where('is_active', true)
            ->where('impressions', 0)
            ->get();

        return $ctas->map(fn ($cta) => [
            'id' => $cta->id,
            'uuid' => $cta->uuid,
            'label' => $cta->label,
            'type' => $cta->type,
            'funnel_stage' => $cta->funnel_stage,
            'target_url' => $cta->target_url,
            'target_page_id' => $cta->target_page_id,
        ])->values()->toArray();
    }
}
