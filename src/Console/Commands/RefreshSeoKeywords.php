<?php

namespace Platform\Brands\Console\Commands;

use Illuminate\Console\Command;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoKeywordService;

class RefreshSeoKeywords extends Command
{
    protected $signature = 'brands:refresh-seo-keywords';

    protected $description = 'Aktualisiert SEO-Keyword-Metriken und Rankings für Boards mit fälligem Refresh';

    public function handle(SeoKeywordService $keywordService): int
    {
        $boards = BrandsSeoBoard::query()
            ->where('done', false)
            ->get()
            ->filter(fn(BrandsSeoBoard $board) => $board->isRefreshDue());

        if ($boards->isEmpty()) {
            $this->info('Keine SEO Boards mit fälligem Refresh gefunden.');
            return self::SUCCESS;
        }

        $this->info("Aktualisiere {$boards->count()} SEO Board(s)...");

        $totalMetrics = 0;
        $totalRankings = 0;
        $totalCost = 0;

        foreach ($boards as $board) {
            $this->line("  → {$board->name} (ID: {$board->id})");

            // 1. Metriken abrufen (Search Volume, CPC etc.)
            $metricsResult = $keywordService->fetchMetrics($board);

            if (isset($metricsResult['error'])) {
                $this->warn("    Budget-Limit erreicht: {$metricsResult['error']}");
                continue;
            }

            $boardMetrics = $metricsResult['fetched'];
            $boardCost = $metricsResult['cost_cents'];
            $totalMetrics += $boardMetrics;

            $this->info("    Metriken: {$boardMetrics} Keywords aktualisiert ({$boardCost} Cents)");

            // 2. Rankings abrufen (nur wenn Keywords mit target_url vorhanden)
            $hasTargetUrls = $board->keywords()->whereNotNull('target_url')->where('target_url', '!=', '')->exists();

            if ($hasTargetUrls) {
                $rankingsResult = $keywordService->fetchRankings($board);

                if (isset($rankingsResult['error'])) {
                    $this->warn("    Rankings: Budget-Limit erreicht: {$rankingsResult['error']}");
                } else {
                    $boardRankings = $rankingsResult['position_snapshots'];
                    $rankingsCost = $rankingsResult['cost_cents'];
                    $totalRankings += $boardRankings;
                    $boardCost += $rankingsCost;

                    $this->info("    Rankings: {$rankingsResult['fetched']} Keywords geprüft, {$boardRankings} Positionen getrackt ({$rankingsCost} Cents)");
                }
            } else {
                $this->line('    Rankings: Übersprungen (keine Keywords mit target_url)');
            }

            $totalCost += $boardCost;
            $this->info("    Gesamt: {$boardCost} Cents");
        }

        $this->newLine();
        $this->info("Fertig. Metriken: {$totalMetrics}, Rankings: {$totalRankings}, Kosten: {$totalCost} Cents");

        return self::SUCCESS;
    }
}
