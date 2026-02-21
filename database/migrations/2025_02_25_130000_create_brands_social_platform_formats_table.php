<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_social_platform_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained('brands_social_platforms')->onDelete('cascade');
            $table->string('name');
            $table->string('key');
            $table->string('aspect_ratio')->nullable();
            $table->string('media_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();

            $table->unique(['platform_id', 'key']);
            $table->index(['platform_id']);
            $table->index(['is_active']);
            $table->index(['media_type']);
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_social_platform_formats');
    }
};
