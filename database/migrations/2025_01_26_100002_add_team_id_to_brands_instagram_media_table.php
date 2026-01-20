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
            
            // Prüfe ob Spalte bereits existiert
            $columnExists = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.columns 
                 WHERE table_schema = ? AND table_name = ? AND column_name = ?",
                [$databaseName, $tableName, 'team_id']
            );

            if ($columnExists[0]->count == 0) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('team_id')->nullable()->after('user_id')->constrained('teams')->onDelete('cascade');
                    $table->index(['team_id'], 'bim_team_id_idx');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = 'brands_instagram_media';
        
        if (Schema::hasTable($tableName)) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['team_id']);
                $table->dropIndex('bim_team_id_idx');
                $table->dropColumn('team_id');
            });
        }
    }
};
