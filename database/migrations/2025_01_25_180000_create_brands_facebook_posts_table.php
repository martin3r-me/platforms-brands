<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'brands_facebook_posts';
        
        // Tabelle erstellen, falls sie nicht existiert
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('facebook_page_id')->constrained('integrations_facebook_pages')->onDelete('cascade');
                $table->string('external_id'); // Facebook Post ID
                $table->text('message')->nullable(); // Post-Text
                $table->text('story')->nullable(); // Story-Text
                $table->string('type')->nullable(); // Post-Typ (photo, video, status, etc.)
                $table->text('media_url')->nullable(); // URL für Bild oder Video
                $table->text('permalink_url')->nullable(); // Permalink zum Post
                $table->timestamp('published_at')->nullable(); // Veröffentlichungszeit
                $table->timestamp('scheduled_publish_time')->nullable(); // Geplante Veröffentlichungszeit
                $table->string('status')->nullable(); // Status: draft, published, failed
                $table->integer('like_count')->default(0);
                $table->integer('comment_count')->default(0);
                $table->integer('share_count')->default(0);
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();
                
                $table->index(['facebook_page_id'], 'bfp_page_id_idx');
                $table->index(['external_id'], 'bfp_external_id_idx');
                $table->index(['published_at'], 'bfp_published_at_idx');
                $table->index(['status'], 'bfp_status_idx');
                $table->unique(['external_id'], 'bfp_external_id_uniq');
            });
        } else {
            // Tabelle existiert bereits - nur Indizes hinzufügen, falls sie nicht existieren
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            // Indizes hinzufügen, falls nicht vorhanden
            $indexes = [
                [['facebook_page_id'], 'bfp_page_id_idx'],
                [['external_id'], 'bfp_external_id_idx'],
                [['published_at'], 'bfp_published_at_idx'],
                [['status'], 'bfp_status_idx'],
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
                [$databaseName, $tableName, 'bfp_external_id_uniq']
            );
            
            if ($uniqueExists[0]->count == 0) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unique(['external_id'], 'bfp_external_id_uniq');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_facebook_posts');
    }
};
