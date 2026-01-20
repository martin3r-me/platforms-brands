<?php

namespace Platform\Brands\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Command zum sicheren Truncaten von Integrations- und Brands-Daten
 * 
 * Beachtet die korrekte Reihenfolge der Foreign Key Constraints
 */
class TruncateIntegrationsData extends Command
{
    protected $signature = 'brands:truncate-integrations-data 
                            {--confirm : Ask for confirmation before truncating}';

    protected $description = 'Truncate all integrations and brands data (Facebook, Instagram, WhatsApp) in correct order';

    public function handle()
    {
        if ($this->option('confirm')) {
            if (!$this->confirm('⚠️  WARNUNG: Dies wird ALLE Integrations- und Brands-Daten löschen. Fortfahren?')) {
                $this->info('Abgebrochen.');
                return Command::SUCCESS;
            }
        }

        $this->info('🔄 Starte Truncate-Prozess...');
        $this->newLine();

        // Foreign Key Constraints temporär deaktivieren
        Schema::disableForeignKeyConstraints();

        try {
            // Reihenfolge beachten: Zuerst abhängige Tabellen, dann Basis-Tabellen
            
            // 1. Brands Instagram Media (abhängig von integrations_instagram_accounts)
            if (Schema::hasTable('brands_instagram_media')) {
                $count = DB::table('brands_instagram_media')->count();
                DB::table('brands_instagram_media')->truncate();
                $this->info("  ✅ brands_instagram_media: {$count} Einträge gelöscht");
            }

            // 2. Brands Instagram Account Insights (abhängig von integrations_instagram_accounts)
            if (Schema::hasTable('brands_instagram_account_insights')) {
                $count = DB::table('brands_instagram_account_insights')->count();
                DB::table('brands_instagram_account_insights')->truncate();
                $this->info("  ✅ brands_instagram_account_insights: {$count} Einträge gelöscht");
            }

            // 3. Brands Instagram Media Insights (falls vorhanden)
            if (Schema::hasTable('brands_instagram_media_insights')) {
                $count = DB::table('brands_instagram_media_insights')->count();
                DB::table('brands_instagram_media_insights')->truncate();
                $this->info("  ✅ brands_instagram_media_insights: {$count} Einträge gelöscht");
            }

            // 4. Brands Instagram Media Comments (falls vorhanden)
            if (Schema::hasTable('brands_instagram_media_comments')) {
                $count = DB::table('brands_instagram_media_comments')->count();
                DB::table('brands_instagram_media_comments')->truncate();
                $this->info("  ✅ brands_instagram_media_comments: {$count} Einträge gelöscht");
            }

            // 5. Brands Facebook Posts (abhängig von integrations_facebook_pages)
            if (Schema::hasTable('brands_facebook_posts')) {
                $count = DB::table('brands_facebook_posts')->count();
                DB::table('brands_facebook_posts')->truncate();
                $this->info("  ✅ brands_facebook_posts: {$count} Einträge gelöscht");
            }

            // 6. Integrations Instagram Accounts (abhängig von integrations_facebook_pages)
            if (Schema::hasTable('integrations_instagram_accounts')) {
                $count = DB::table('integrations_instagram_accounts')->count();
                DB::table('integrations_instagram_accounts')->truncate();
                $this->info("  ✅ integrations_instagram_accounts: {$count} Einträge gelöscht");
            }

            // 7. Integrations Facebook Pages (Basis-Tabelle)
            if (Schema::hasTable('integrations_facebook_pages')) {
                $count = DB::table('integrations_facebook_pages')->count();
                DB::table('integrations_facebook_pages')->truncate();
                $this->info("  ✅ integrations_facebook_pages: {$count} Einträge gelöscht");
            }

            // 8. Integrations Meta Business Accounts (optional, falls vorhanden)
            // MUSS vor WhatsApp Accounts kommen, da WhatsApp Accounts FK zu Business Accounts haben
            if (Schema::hasTable('integrations_meta_business_accounts')) {
                $count = DB::table('integrations_meta_business_accounts')->count();
                DB::table('integrations_meta_business_accounts')->truncate();
                $this->info("  ✅ integrations_meta_business_accounts: {$count} Einträge gelöscht");
            }

            // 9. Integrations WhatsApp Accounts (optional, falls vorhanden)
            if (Schema::hasTable('integrations_whatsapp_accounts')) {
                $count = DB::table('integrations_whatsapp_accounts')->count();
                DB::table('integrations_whatsapp_accounts')->truncate();
                $this->info("  ✅ integrations_whatsapp_accounts: {$count} Einträge gelöscht");
            }

            $this->newLine();
            $this->info('✅ Alle Integrations- und Brands-Daten erfolgreich gelöscht!');
            
        } catch (\Exception $e) {
            $this->error('❌ Fehler beim Truncaten: ' . $e->getMessage());
            return Command::FAILURE;
        } finally {
            // Foreign Key Constraints wieder aktivieren
            Schema::enableForeignKeyConstraints();
        }

        return Command::SUCCESS;
    }
}
