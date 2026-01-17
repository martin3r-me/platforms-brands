<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'brands_instagram_media_insights';
        
        // Tabelle erstellen, falls sie nicht existiert
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->foreignId('instagram_media_id')->constrained('brands_instagram_media')->onDelete('cascade');
                $table->date('insight_date');
                
                // Basis-Metriken
                $table->integer('impressions')->nullable();
                $table->integer('reach')->nullable();
                $table->integer('saved')->nullable();
                $table->integer('comments')->nullable();
                $table->integer('likes')->nullable();
                $table->integer('shares')->nullable();
                $table->integer('total_interactions')->nullable();
                
                // Story-spezifische Metriken
                $table->integer('replies')->nullable();
                $table->json('navigation')->nullable(); // Story Navigation Actions
                
                // Reel-spezifische Metriken
                $table->integer('plays')->nullable();
                $table->integer('clips_replays_count')->nullable();
                $table->integer('ig_reels_aggregated_all_plays_count')->nullable();
                $table->integer('ig_reels_avg_watch_time')->nullable();
                $table->integer('ig_reels_video_view_total_time')->nullable();
                
                $table->timestamps();
                
                $table->index(['instagram_media_id', 'insight_date'], 'bim_insights_media_date_idx');
                $table->unique(['instagram_media_id', 'insight_date'], 'bim_insights_media_date_uniq');
            });
        } else {
            // Tabelle existiert bereits - nur Indizes hinzufügen, falls sie nicht existieren
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            // Index hinzufügen, falls nicht vorhanden
            $indexExists = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $tableName, 'bim_insights_media_date_idx']
            );
            
            if ($indexExists[0]->count == 0) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->index(['instagram_media_id', 'insight_date'], 'bim_insights_media_date_idx');
                });
            }
            
            // Unique Constraint hinzufügen, falls nicht vorhanden
            $uniqueExists = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $tableName, 'bim_insights_media_date_uniq']
            );
            
            if ($uniqueExists[0]->count == 0) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unique(['instagram_media_id', 'insight_date'], 'bim_insights_media_date_uniq');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_instagram_media_insights');
    }
};
