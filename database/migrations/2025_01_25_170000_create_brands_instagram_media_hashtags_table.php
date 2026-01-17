<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'brands_instagram_media_hashtags';
        
        // Tabelle erstellen, falls sie nicht existiert
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('instagram_media_id')->constrained('brands_instagram_media')->onDelete('cascade');
                $table->foreignId('hashtag_id')->constrained('brands_instagram_hashtags')->onDelete('cascade');
                $table->integer('count')->default(1); // Wie oft der Hashtag in diesem Media vorkommt
                $table->timestamps();
                
                $table->index(['instagram_media_id'], 'bimh_media_id_idx');
                $table->index(['hashtag_id'], 'bimh_hashtag_id_idx');
                $table->unique(['instagram_media_id', 'hashtag_id'], 'bimh_media_hashtag_uniq');
            });
        } else {
            // Tabelle existiert bereits - nur Indizes hinzufügen, falls sie nicht existieren
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            // Indizes hinzufügen, falls nicht vorhanden
            $indexes = [
                ['instagram_media_id', 'bimh_media_id_idx'],
                ['hashtag_id', 'bimh_hashtag_id_idx'],
            ];
            
            foreach ($indexes as [$column, $indexName]) {
                $indexExists = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $tableName, $indexName]
                );
                
                if ($indexExists[0]->count == 0) {
                    Schema::table($tableName, function (Blueprint $table) use ($column, $indexName) {
                        $table->index([$column], $indexName);
                    });
                }
            }
            
            // Unique Constraint hinzufügen, falls nicht vorhanden
            $uniqueExists = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $tableName, 'bimh_media_hashtag_uniq']
            );
            
            if ($uniqueExists[0]->count == 0) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unique(['instagram_media_id', 'hashtag_id'], 'bimh_media_hashtag_uniq');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_instagram_media_hashtags');
    }
};
