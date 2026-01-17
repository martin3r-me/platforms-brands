<?php

namespace Platform\Brands\Console\Commands;

use Illuminate\Console\Command;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Services\FacebookPageService;

class SyncFacebookPages extends Command
{
    protected $signature = 'brands:sync-facebook-pages 
                            {--brand-id= : Specific brand ID to sync}
                            {--team-id= : Sync for all brands in a specific team}
                            {--dry-run : Show what would be synced without actually doing it}';

    protected $description = 'Synchronize Facebook Pages for brands';

    public function handle(FacebookPageService $service)
    {
        $isDryRun = $this->option('dry-run');
        $brandId = $this->option('brand-id');
        $teamId = $this->option('team-id');

        if ($isDryRun) {
            $this->info('🔍 DRY-RUN Modus - keine Daten werden synchronisiert');
        }

        $this->info('🔄 Starte Facebook Pages Synchronisation...');
        $this->newLine();

        // Brands finden
        $query = BrandsBrand::query();

        if ($brandId) {
            $query->where('id', $brandId);
        } elseif ($teamId) {
            $query->where('team_id', $teamId);
        }

        $brands = $query->with('metaToken')->get();

        if ($brands->isEmpty()) {
            $this->warn('⚠️  Keine Brands gefunden.');
            return Command::SUCCESS;
        }

        $this->info("📋 {$brands->count()} Brand(s) gefunden:");
        $this->newLine();

        $syncedCount = 0;
        $skippedCount = 0;

        foreach ($brands as $brand) {
            $this->info("  📝 Verarbeite Brand: '{$brand->name}' (ID: {$brand->id})");

            // Prüfe ob Meta Token vorhanden
            if (!$brand->metaToken) {
                $this->warn("     ⚠️  Übersprungen: Kein Meta Token vorhanden");
                $skippedCount++;
                continue;
            }

            if ($isDryRun) {
                $this->info("     🔍 Würde Facebook Pages synchronisieren");
                $syncedCount++;
                continue;
            }

            try {
                $result = $service->syncFacebookPages($brand);
                $pagesCount = count($result);
                $this->info("     ✅ {$pagesCount} Facebook Page(s) synchronisiert");
                $syncedCount++;
            } catch (\Exception $e) {
                $this->error("     ❌ Fehler: {$e->getMessage()}");
                $skippedCount++;
            }
        }

        $this->newLine();

        if ($isDryRun) {
            $this->warn("🔍 DRY-RUN: {$syncedCount} Brand(s) würden synchronisiert, {$skippedCount} übersprungen");
        } else {
            $this->info("✅ {$syncedCount} Brand(s) erfolgreich synchronisiert, {$skippedCount} übersprungen");
        }

        return Command::SUCCESS;
    }
}
