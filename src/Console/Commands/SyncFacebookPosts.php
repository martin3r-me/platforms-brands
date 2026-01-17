<?php

namespace Platform\Brands\Console\Commands;

use Illuminate\Console\Command;
use Platform\Brands\Models\FacebookPage;
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
        $query = FacebookPage::query();

        if ($pageId) {
            $query->where('id', $pageId);
        } elseif ($brandId) {
            // Pages über core_service_assets Pivot-Tabelle finden
            $query->whereHas('services', function ($q) use ($brandId) {
                $q->where('service_type', \Platform\Brands\Models\BrandsBrand::class)
                  ->where('service_id', $brandId);
            });
        } elseif ($teamId) {
            $query->where('team_id', $teamId);
        }

        $pages = $query->with(['user', 'team'])->get();

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

            // Prüfe ob Meta Token vorhanden (vom User/Team)
            $metaToken = \Platform\Brands\Models\MetaToken::where('user_id', $page->user_id)
                ->where('team_id', $page->team_id)
                ->first();
            
            if (!$metaToken) {
                $this->warn("     ⚠️  Übersprungen: Kein Meta Token für User/Team vorhanden");
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
