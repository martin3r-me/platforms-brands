<?php

namespace Platform\Brands\Console\Commands;

use Illuminate\Console\Command;
use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Platform\Brands\Services\InstagramInsightsService;

class SyncInstagramInsights extends Command
{
    protected $signature = 'brands:sync-instagram-insights 
                            {--account-id= : Specific Instagram Account ID to sync}
                            {--brand-id= : Sync for all accounts of a specific brand}
                            {--media-only : Only sync media insights}
                            {--account-only : Only sync account insights}
                            {--dry-run : Show what would be synced without actually doing it}';

    protected $description = 'Synchronize Instagram Insights for accounts and media';

    public function handle(InstagramInsightsService $service)
    {
        $isDryRun = $this->option('dry-run');
        $accountId = $this->option('account-id');
        $brandId = $this->option('brand-id');
        $mediaOnly = $this->option('media-only');
        $accountOnly = $this->option('account-only');

        if ($isDryRun) {
            $this->info('🔍 DRY-RUN Modus - keine Daten werden synchronisiert');
        }

        $this->info('🔄 Starte Instagram Insights Synchronisation...');
        $this->newLine();

        // Accounts finden
        $query = IntegrationsInstagramAccount::query();

        if ($accountId) {
            $query->where('id', $accountId);
        } elseif ($brandId) {
            // TODO: Accounts über Brand-Verknüpfung finden, wenn implementiert
            $this->warn('⚠️  --brand-id Option wird aktuell nicht unterstützt (Verknüpfung noch nicht implementiert)');
            return Command::SUCCESS;
        }

        $accounts = $query->with(['user'])->get();

        if ($accounts->isEmpty()) {
            $this->warn('⚠️  Keine Instagram Accounts gefunden.');
            return Command::SUCCESS;
        }

        $this->info("📋 {$accounts->count()} Instagram Account(s) gefunden:");
        $this->newLine();

        $syncedCount = 0;
        $skippedCount = 0;

        foreach ($accounts as $account) {
            $this->info("  📝 Verarbeite Account: '{$account->username}' (ID: {$account->id})");

            // Prüfe ob Meta Connection vorhanden (vom User)
            $user = \Platform\Core\Models\User::find($account->user_id);
            
            if (!$user) {
                $this->warn("     ⚠️  Übersprungen: Kein User für Account gefunden");
                $skippedCount++;
                continue;
            }
            
            $metaService = app(\Platform\Integrations\Services\MetaIntegrationService::class);
            $accessToken = $metaService->getValidAccessTokenForUser($user);
            
            if (!$accessToken) {
                $this->warn("     ⚠️  Übersprungen: Kein Meta Token für User vorhanden. Bitte zuerst Meta über OAuth verbinden.");
                $skippedCount++;
                continue;
            }

            if ($isDryRun) {
                $this->info("     🔍 Würde Instagram Insights synchronisieren");
                $syncedCount++;
                continue;
            }

            try {
                // Account Insights
                if (!$mediaOnly) {
                    $this->info("     📊 Synchronisiere Account Insights...");
                    $accountInsights = $service->syncAccountInsights($account);
                    $this->info("     ✅ Account Insights synchronisiert");
                }

                // Media Insights
                if (!$accountOnly) {
                    $this->info("     📊 Synchronisiere Media Insights...");
                    $mediaResults = $service->syncMediaInsights($account);
                    $this->info("     ✅ {$mediaResults['synced']} Media Insights synchronisiert, {$mediaResults['skipped']} übersprungen");
                }

                $this->info("     ✅ Insights synchronisiert");
                $syncedCount++;
            } catch (\Exception $e) {
                $this->error("     ❌ Fehler: {$e->getMessage()}");
                $skippedCount++;
            }
        }

        $this->newLine();

        if ($isDryRun) {
            $this->warn("🔍 DRY-RUN: {$syncedCount} Account(s) würden synchronisiert, {$skippedCount} übersprungen");
        } else {
            $this->info("✅ {$syncedCount} Account(s) erfolgreich synchronisiert, {$skippedCount} übersprungen");
        }

        return Command::SUCCESS;
    }
}
