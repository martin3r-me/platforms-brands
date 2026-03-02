<?php

namespace Platform\Brands\Console\Commands;

use Illuminate\Console\Command;
use Platform\Brands\Services\ContentBriefRankingService;

class TrackContentBriefRankings extends Command
{
    protected $signature = 'brands:track-brief-rankings
                            {--team= : Optional: Nur für ein bestimmtes Team tracken}';

    protected $description = 'Trackt SERP-Rankings für alle veröffentlichten Content Briefs mit target_url (Weekly)';

    public function handle(ContentBriefRankingService $rankingService): int
    {
        $teamId = $this->option('team') ? (int) $this->option('team') : null;

        $this->info('Starte Content Brief Ranking-Tracking...');

        if ($teamId) {
            $this->info("Einschränkung auf Team ID: {$teamId}");
        }

        $result = $rankingService->trackAllPublishedBriefs($teamId);

        if ($result['briefs_processed'] === 0) {
            $this->info('Keine veröffentlichten Content Briefs mit target_url gefunden.');
            return self::SUCCESS;
        }

        $this->info("Verarbeitet: {$result['briefs_processed']} Brief(s)");
        $this->newLine();

        foreach ($result['results'] as $briefResult) {
            $name = $briefResult['brief_name'] ?? 'Unbekannt';

            if (isset($briefResult['error'])) {
                $this->warn("  ✗ {$name}: {$briefResult['error']}");
                continue;
            }

            $tracked = $briefResult['tracked'];
            $matched = $briefResult['matched'];
            $notFound = $briefResult['not_found'];
            $cost = $briefResult['cost_cents'];

            $this->line("  → {$name}");
            $this->info("    Keywords: {$tracked} getrackt, {$matched} URL-Match, {$notFound} nicht gefunden ({$cost} Cents)");
        }

        $this->newLine();
        $this->info("Gesamt: {$result['total_keywords_tracked']} Keywords, {$result['total_cost_cents']} Cents");

        return self::SUCCESS;
    }
}
