<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = 'brands_instagram_media';
        
        if (Schema::hasTable($tableName)) {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            // Finde alle Foreign Keys für instagram_account_id (auch mit Standard-Namen)
            $foreignKeys = DB::select(
                "SELECT k.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME
                 FROM information_schema.KEY_COLUMN_USAGE k
                 JOIN information_schema.TABLE_CONSTRAINTS c 
                   ON k.CONSTRAINT_NAME = c.CONSTRAINT_NAME 
                   AND k.CONSTRAINT_SCHEMA = c.CONSTRAINT_SCHEMA
                 WHERE k.CONSTRAINT_SCHEMA = ? 
                 AND k.TABLE_NAME = ? 
                 AND k.COLUMN_NAME = 'instagram_account_id'
                 AND c.CONSTRAINT_TYPE = 'FOREIGN KEY'",
                [$databaseName, $tableName]
            );
            
            // Lösche alle Foreign Keys für diese Spalte
            foreach ($foreignKeys as $fk) {
                $constraintName = $fk->CONSTRAINT_NAME;
                
                // Verwende direkten SQL-Befehl, da Laravel's dropForeign manchmal Probleme hat
                try {
                    DB::statement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`");
                } catch (\Exception $e) {
                    // Versuche mit Laravel's Schema Builder als Fallback
                    try {
                        Schema::table($tableName, function (Blueprint $table) use ($constraintName) {
                            $table->dropForeign($constraintName);
                        });
                    } catch (\Exception $e2) {
                        \Log::warning("Could not drop foreign key: {$constraintName}", [
                            'error1' => $e->getMessage(),
                            'error2' => $e2->getMessage()
                        ]);
                    }
                }
            }
            
            // Prüfe ob korrekter Foreign Key bereits existiert
            $correctForeignKey = DB::select(
                "SELECT k.CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE k
                 JOIN information_schema.TABLE_CONSTRAINTS c 
                   ON k.CONSTRAINT_NAME = c.CONSTRAINT_NAME 
                   AND k.CONSTRAINT_SCHEMA = c.CONSTRAINT_SCHEMA
                 WHERE k.CONSTRAINT_SCHEMA = ? 
                 AND k.TABLE_NAME = ? 
                 AND k.COLUMN_NAME = 'instagram_account_id'
                 AND k.REFERENCED_TABLE_NAME = 'integrations_instagram_accounts'
                 AND c.CONSTRAINT_TYPE = 'FOREIGN KEY'",
                [$databaseName, $tableName]
            );
            
            // Erstelle korrekten Foreign Key nur wenn nicht vorhanden
            if (empty($correctForeignKey)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreign('instagram_account_id', 'bim_instagram_account_id_fk')
                        ->references('id')
                        ->on('integrations_instagram_accounts')
                        ->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keine Down-Migration nötig, da wir nur korrigieren
    }
};
