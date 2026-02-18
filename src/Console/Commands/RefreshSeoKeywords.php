<?php

namespace Platform\Brands\Console\Commands;

use Illuminate\Console\Command;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoKeywordService;

class RefreshSeoKeywords extends Command
{
    protected $signature = 'brands:refresh-seo-keywords';

    protected $description = 'Aktualisiert SEO-Keyword-Metriken für Boards mit fälligem Refresh';

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

        foreach ($boards as $board) {
            $this->line("  → {$board->name} (ID: {$board->id})");

            $result = $keywordService->fetchMetrics($board);

            if (isset($result['error'])) {
                $this->warn("    Budget-Limit erreicht: {$result['error']}");
                continue;
            }

            $this->info("    {$result['fetched']} Keywords aktualisiert, Kosten: {$result['cost_cents']} Cents");
        }

        $this->info('Fertig.');
        return self::SUCCESS;
    }
}
