<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_ctas', function (Blueprint $table) {
            $table->unsignedBigInteger('impressions')->default(0)->after('order');
            $table->unsignedBigInteger('clicks')->default(0)->after('impressions');
            $table->timestamp('last_clicked_at')->nullable()->after('clicks');

            $table->index(['brand_id', 'clicks']);
            $table->index(['brand_id', 'impressions']);
        });
    }

    public function down(): void
    {
        Schema::table('brand_ctas', function (Blueprint $table) {
            $table->dropIndex(['brand_id', 'clicks']);
            $table->dropIndex(['brand_id', 'impressions']);
            $table->dropColumn(['impressions', 'clicks', 'last_clicked_at']);
        });
    }
};
