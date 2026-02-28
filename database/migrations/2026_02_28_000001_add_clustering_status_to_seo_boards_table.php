<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands_seo_boards', function (Blueprint $table) {
            $table->string('clustering_status')->nullable()->after('dataforseo_config');
            $table->json('clustering_result')->nullable()->after('clustering_status');
            $table->timestamp('clustering_started_at')->nullable()->after('clustering_result');
            $table->timestamp('clustering_completed_at')->nullable()->after('clustering_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('brands_seo_boards', function (Blueprint $table) {
            $table->dropColumn([
                'clustering_status',
                'clustering_result',
                'clustering_started_at',
                'clustering_completed_at',
            ]);
        });
    }
};
