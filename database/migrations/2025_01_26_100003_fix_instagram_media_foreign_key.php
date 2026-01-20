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
            
            // Finde alle Foreign Keys für instagram_account_id
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
                try {
                    Schema::table($tableName, function (Blueprint $table) use ($constraintName) {
                        $table->dropForeign([$constraintName]);
                    });
                } catch (\Exception $e) {
                    // Ignoriere Fehler, falls Constraint nicht existiert
                }
            }
            
            // Erstelle korrekten Foreign Key
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('instagram_account_id', 'bim_instagram_account_id_fk')
                    ->references('id')
                    ->on('integrations_instagram_accounts')
                    ->onDelete('cascade');
            });
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
