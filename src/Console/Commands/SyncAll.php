<?php

namespace Platform\Brands\Console\Commands;

use Illuminate\Console\Command;

class SyncAll extends Command
{
    protected $signature = 'brands:sync-all 
                            {--brand-id= : Specific brand ID to sync}
                            {--team-id= : Sync for all brands in a specific team}
                            {--skip-media : Skip media synchronization}
                            {--skip-insights : Skip insights synchronization}
                            {--skip-comments : Skip comments synchronization}
                            {--skip-hashtags : Skip hashtags synchronization}
                            {--dry-run : Show what would be synced without actually doing it}';

    protected $description = 'Synchronize all social media data for brands (Pages, Accounts, Media, Insights, etc.)';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $brandId = $this->option('brand-id');
        $teamId = $this->option('team-id');

        if ($isDryRun) {
            $this->info('🔍 DRY-RUN Modus - keine Daten werden synchronisiert');
        }

        $this->info('🚀 Starte vollständige Social Media Synchronisation...');
        $this->newLine();

        $options = [];
        if ($brandId) {
            $options['--brand-id'] = $brandId;
        } elseif ($teamId) {
            $options['--team-id'] = $teamId;
        }
        if ($isDryRun) {
            $options['--dry-run'] = true;
        }

        $commands = [
            'brands:sync-facebook-pages' => 'Facebook Pages',
            'brands:sync-facebook-posts' => 'Facebook Posts',
            'brands:sync-instagram-accounts' => 'Instagram Accounts',
        ];

        if (!$this->option('skip-media')) {
            $commands['brands:sync-instagram-media'] = 'Instagram Media';
        }

        if (!$this->option('skip-insights')) {
            $commands['brands:sync-instagram-insights'] = 'Instagram Insights';
        }

        // TODO: Commands für Comments und Hashtags hinzufügen, wenn Services implementiert sind

        $successCount = 0;
        $failedCount = 0;

        foreach ($commands as $command => $label) {
            $this->info("📦 Synchronisiere {$label}...");
            $this->newLine();

            try {
                $exitCode = $this->call($command, $options);
                
                if ($exitCode === Command::SUCCESS) {
                    $successCount++;
                    $this->info("✅ {$label} erfolgreich synchronisiert");
                } else {
                    $failedCount++;
                    $this->error("❌ {$label} Synchronisation fehlgeschlagen");
                }
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("❌ Fehler bei {$label}: {$e->getMessage()}");
            }

            $this->newLine();
        }

        $this->info("🎉 Synchronisation abgeschlossen!");
        $this->info("✅ {$successCount} erfolgreich, ❌ {$failedCount} fehlgeschlagen");

        return $failedCount === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
