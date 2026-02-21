<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands_content_boards', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('description');
            $table->string('slug')->nullable()->after('domain');
            $table->string('published_url')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('brands_content_boards', function (Blueprint $table) {
            $table->dropColumn(['domain', 'slug', 'published_url']);
        });
    }
};
