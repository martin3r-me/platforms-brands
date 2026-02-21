<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands_social_platform_formats', function (Blueprint $table) {
            $table->json('output_schema')->nullable()->after('media_type');
            $table->json('rules')->nullable()->after('output_schema');
        });
    }

    public function down(): void
    {
        Schema::table('brands_social_platform_formats', function (Blueprint $table) {
            $table->dropColumn(['output_schema', 'rules']);
        });
    }
};
