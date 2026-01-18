<?php

namespace Platform\Brands\Console\Commands;

use Illuminate\Console\Command;
use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Platform\Brands\Services\InstagramMediaService;

class SyncInstagramMedia extends Command
{
    protected $signature = 'brands:sync-instagram-media 
                            {--account-id= : Specific Instagram Account ID to sync}
                            {--brand-id= : Sync for all accounts of a specific brand}
                            {--limit=1000 : Maximum number of media items to fetch}
                            {--dry-run : Show what would be synced without actually doing it}';

    protected $description = 'Synchronize Instagram Media for accounts';

    public function handle(InstagramMediaService $service)
    {
        $isDryRun = $this->option('dry-run');
        $accountId = $this->option('account-id');
        $brandId = $this->option('brand-id');
        $limit = (int) $this->option('limit');

        if ($isDryRun) {
            $this->info('🔍 DRY-RUN Modus - keine Daten werden synchronisiert');
        }

        $this->info('🔄 Starte Instagram Media Synchronisation...');
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

            // Prüfe ob Meta Token vorhanden (vom User)
            $metaToken = \Platform\Integrations\Models\IntegrationsMetaToken::where('user_id', $account->user_id)
                ->first();
            
            if (!$metaToken) {
                $this->warn("     ⚠️  Übersprungen: Kein Meta Token für User vorhanden");
                $skippedCount++;
                continue;
            }

            if ($isDryRun) {
                $this->info("     🔍 Würde Instagram Media synchronisieren (Limit: {$limit})");
                $syncedCount++;
                continue;
            }

            try {
                $result = $service->syncMedia($account, $limit, $this);
                $mediaCount = count($result);
                $this->info("     ✅ {$mediaCount} Media-Item(s) synchronisiert");
                $syncedCount++;
            } catch (\Exception $e) {
                $this->error("     ❌ Fehler: {$e->getMessage()}");
                $this->error("     Stack: " . $e->getTraceAsString());
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
