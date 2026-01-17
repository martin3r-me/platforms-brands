<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'brands_instagram_media_comments';
        
        // Tabelle erstellen, falls sie nicht existiert
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('instagram_media_id')->constrained('brands_instagram_media')->onDelete('cascade');
                $table->string('external_id'); // Instagram Comment ID
                $table->text('text')->nullable();
                $table->string('username')->nullable();
                $table->integer('like_count')->default(0);
                $table->timestamp('timestamp')->nullable(); // Instagram Timestamp
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();
                
                $table->index(['instagram_media_id'], 'bimc_media_id_idx');
                $table->index(['external_id'], 'bimc_external_id_idx');
                $table->unique(['external_id'], 'bimc_external_id_uniq');
            });
        } else {
            // Tabelle existiert bereits - nur Indizes hinzufügen, falls sie nicht existieren
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            // Indizes hinzufügen, falls nicht vorhanden
            $indexes = [
                [['instagram_media_id'], 'bimc_media_id_idx'],
                [['external_id'], 'bimc_external_id_idx'],
            ];
            
            foreach ($indexes as [$columns, $indexName]) {
                $indexExists = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $tableName, $indexName]
                );
                
                if ($indexExists[0]->count == 0) {
                    Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                        $table->index($columns, $indexName);
                    });
                }
            }
            
            // Unique Constraint hinzufügen, falls nicht vorhanden
            $uniqueExists = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $tableName, 'bimc_external_id_uniq']
            );
            
            if ($uniqueExists[0]->count == 0) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unique(['external_id'], 'bimc_external_id_uniq');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_instagram_media_comments');
    }
};
