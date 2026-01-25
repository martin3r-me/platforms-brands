<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'brands_instagram_account_insights';
        
        // Tabelle erstellen, falls sie nicht existiert
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                
                // Foreign Key nur erstellen, wenn die Tabelle existiert
                $table->foreignId('instagram_account_id');
                if (Schema::hasTable('integrations_instagram_accounts')) {
                    $table->foreign('instagram_account_id')
                        ->references('id')
                        ->on('integrations_instagram_accounts')
                        ->onDelete('cascade');
                }
                $table->date('insight_date');
                
                // Account-Details
                $table->string('current_name')->nullable();
                $table->string('current_username')->nullable();
                $table->text('current_biography')->nullable();
                $table->text('current_profile_picture_url')->nullable();
                $table->text('current_website')->nullable();
                $table->integer('current_followers')->nullable();
                $table->integer('current_follows')->nullable();
                
                // Tägliche Metriken
                $table->integer('follower_count')->nullable();
                $table->integer('impressions')->nullable();
                $table->integer('reach')->nullable();
                
                // Total-Value-Metriken
                $table->integer('accounts_engaged')->nullable();
                $table->integer('total_interactions')->nullable();
                $table->integer('likes')->nullable();
                $table->integer('comments')->nullable();
                $table->integer('shares')->nullable();
                $table->integer('saves')->nullable();
                $table->integer('replies')->nullable();
                
                // Weitere Metriken
                $table->integer('profile_views')->nullable();
                $table->integer('website_clicks')->nullable();
                $table->integer('email_contacts')->nullable();
                $table->integer('phone_call_clicks')->nullable();
                $table->integer('get_directions_clicks')->nullable();
                
                $table->timestamps();
                
                $table->index(['instagram_account_id', 'insight_date'], 'bia_insights_account_date_idx');
                $table->unique(['instagram_account_id', 'insight_date'], 'bia_insights_account_date_uniq');
            });
        } else {
            // Tabelle existiert bereits - nur Indizes hinzufügen, falls sie nicht existieren
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            
            // Index hinzufügen, falls nicht vorhanden
            $indexExists = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $tableName, 'bia_insights_account_date_idx']
            );
            
            if ($indexExists[0]->count == 0) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->index(['instagram_account_id', 'insight_date'], 'bia_insights_account_date_idx');
                });
            }
            
            // Unique Constraint hinzufügen, falls nicht vorhanden
            $uniqueExists = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $tableName, 'bia_insights_account_date_uniq']
            );
            
            if ($uniqueExists[0]->count == 0) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unique(['instagram_account_id', 'insight_date'], 'bia_insights_account_date_uniq');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_instagram_account_insights');
    }
};
