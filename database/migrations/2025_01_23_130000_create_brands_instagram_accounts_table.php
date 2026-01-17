<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'brands_instagram_accounts';
        
        // Tabelle erstellen, falls sie nicht existiert
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('brand_id')->constrained('brands_brands')->onDelete('cascade');
                $table->foreignId('facebook_page_id')->constrained('brands_facebook_pages')->onDelete('cascade');
                $table->string('external_id');
                $table->string('username');
                $table->text('description')->nullable();
                $table->text('access_token')->nullable();
                $table->text('refresh_token')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->string('token_type')->nullable()->default('Bearer');
                $table->json('scopes')->nullable();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
                $table->timestamps();
                
                $table->index(['brand_id'], 'bia_brand_id_idx');
                $table->index(['facebook_page_id'], 'bia_page_id_idx');
                $table->index(['team_id'], 'bia_team_id_idx');
                $table->index(['external_id'], 'bia_external_id_idx');
                $table->index(['expires_at'], 'bia_expires_at_idx');
            });
        } else {
            // Tabelle existiert bereits - nur Indizes hinzufügen, falls sie nicht existieren
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            // Indizes hinzufügen, falls nicht vorhanden
            $indexes = [
                [['brand_id'], 'bia_brand_id_idx'],
                [['facebook_page_id'], 'bia_page_id_idx'],
                [['team_id'], 'bia_team_id_idx'],
                [['external_id'], 'bia_external_id_idx'],
                [['expires_at'], 'bia_expires_at_idx'],
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
        Schema::dropIfExists('brands_instagram_accounts');
    }
};
