<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'brands_instagram_media';
        
        // Tabelle erstellen, falls sie nicht existiert
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('instagram_account_id')->constrained('brands_instagram_accounts')->onDelete('cascade');
                $table->string('external_id'); // Instagram Media ID
                $table->text('caption')->nullable();
                $table->string('media_type'); // IMAGE, VIDEO, CAROUSEL_ALBUM, STORY, REEL
                $table->text('media_url')->nullable();
                $table->text('permalink')->nullable();
                $table->text('thumbnail_url')->nullable();
                $table->timestamp('timestamp')->nullable();
                $table->integer('like_count')->default(0);
                $table->integer('comments_count')->default(0);
                $table->boolean('is_story')->default(false);
                $table->boolean('insights_available')->default(true);
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
                $table->timestamps();
                
                $table->index(['instagram_account_id'], 'bim_account_id_idx');
                $table->index(['external_id'], 'bim_external_id_idx');
                $table->index(['team_id'], 'bim_team_id_idx');
                $table->index(['timestamp'], 'bim_timestamp_idx');
                $table->index(['is_story'], 'bim_is_story_idx');
            });
        } else {
            // Tabelle existiert bereits - nur Indizes hinzufügen, falls sie nicht existieren
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            // Indizes hinzufügen, falls nicht vorhanden
            $indexes = [
                [['instagram_account_id'], 'bim_account_id_idx'],
                [['external_id'], 'bim_external_id_idx'],
                [['team_id'], 'bim_team_id_idx'],
                [['timestamp'], 'bim_timestamp_idx'],
                [['is_story'], 'bim_is_story_idx'],
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
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_instagram_media');
    }
};
