<?php

namespace Platform\Brands\Console\Commands;

use Illuminate\Console\Command;
use Platform\Brands\Models\BrandsFacebookPage;
use Platform\Brands\Services\FacebookPageService;

class SyncFacebookPosts extends Command
{
    protected $signature = 'brands:sync-facebook-posts 
                            {--page-id= : Specific Facebook Page ID to sync}
                            {--brand-id= : Sync for all pages of a specific brand}
                            {--team-id= : Sync for all pages in a specific team}
                            {--dry-run : Show what would be synced without actually doing it}';

    protected $description = 'Synchronize Facebook Posts for pages';

    public function handle(FacebookPageService $service)
    {
        $isDryRun = $this->option('dry-run');
        $pageId = $this->option('page-id');
        $brandId = $this->option('brand-id');
        $teamId = $this->option('team-id');

        if ($isDryRun) {
            $this->info('🔍 DRY-RUN Modus - keine Daten werden synchronisiert');
        }

        $this->info('🔄 Starte Facebook Posts Synchronisation...');
        $this->newLine();

        // Pages finden
        $query = BrandsFacebookPage::query();

        if ($pageId) {
            $query->where('id', $pageId);
        } elseif ($brandId) {
            $query->where('brand_id', $brandId);
        } elseif ($teamId) {
            $query->where('team_id', $teamId);
        }

        $pages = $query->with('brand.metaToken')->get();

        if ($pages->isEmpty()) {
            $this->warn('⚠️  Keine Facebook Pages gefunden.');
            return Command::SUCCESS;
        }

        $this->info("📋 {$pages->count()} Facebook Page(s) gefunden:");
        $this->newLine();

        $syncedCount = 0;
        $skippedCount = 0;

        foreach ($pages as $page) {
            $this->info("  📝 Verarbeite Page: '{$page->name}' (ID: {$page->id})");

            // Prüfe ob Meta Token vorhanden
            if (!$page->brand->metaToken) {
                $this->warn("     ⚠️  Übersprungen: Kein Meta Token für Brand vorhanden");
                $skippedCount++;
                continue;
            }

            if ($isDryRun) {
                $this->info("     🔍 Würde Facebook Posts synchronisieren");
                $syncedCount++;
                continue;
            }

            try {
                $result = $service->syncFacebookPosts($page);
                $postsCount = count($result);
                $this->info("     ✅ {$postsCount} Post(s) synchronisiert");
                $syncedCount++;
            } catch (\Exception $e) {
                $this->error("     ❌ Fehler: {$e->getMessage()}");
                $skippedCount++;
            }
        }

        $this->newLine();

        if ($isDryRun) {
            $this->warn("🔍 DRY-RUN: {$syncedCount} Page(s) würden synchronisiert, {$skippedCount} übersprungen");
        } else {
            $this->info("✅ {$syncedCount} Page(s) erfolgreich synchronisiert, {$skippedCount} übersprungen");
        }

        return Command::SUCCESS;
    }
}
