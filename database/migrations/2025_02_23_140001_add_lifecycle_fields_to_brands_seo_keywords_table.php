<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands_seo_keywords', function (Blueprint $table) {
            $table->string('content_status')->default('none')->after('notes');
            $table->string('target_url')->nullable()->after('content_status');
            $table->string('published_url')->nullable()->after('target_url');
            $table->integer('target_position')->nullable()->after('published_url');
            $table->string('location')->nullable()->after('target_position');

            $table->index(['content_status']);
            $table->index(['location']);
        });
    }

    public function down(): void
    {
        Schema::table('brands_seo_keywords', function (Blueprint $table) {
            $table->dropIndex(['content_status']);
            $table->dropIndex(['location']);
            $table->dropColumn(['content_status', 'target_url', 'published_url', 'target_position', 'location']);
        });
    }
};
