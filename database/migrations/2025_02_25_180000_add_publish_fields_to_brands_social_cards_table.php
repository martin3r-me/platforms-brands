<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands_social_cards', function (Blueprint $table) {
            $table->dateTime('publish_at')->nullable()->after('order');
            $table->dateTime('published_at')->nullable()->after('publish_at');
            $table->string('status')->default('draft')->after('published_at');

            $table->index(['status']);
            $table->index(['publish_at']);
        });
    }

    public function down(): void
    {
        Schema::table('brands_social_cards', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['publish_at']);
            $table->dropColumn(['publish_at', 'published_at', 'status']);
        });
    }
};
