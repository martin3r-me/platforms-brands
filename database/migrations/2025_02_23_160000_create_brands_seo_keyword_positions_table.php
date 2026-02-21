<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_seo_keyword_positions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('seo_keyword_id')->constrained('brands_seo_keywords')->onDelete('cascade');
            $table->integer('position');
            $table->integer('previous_position')->nullable();
            $table->json('serp_features')->nullable();
            $table->timestamp('tracked_at');
            $table->string('search_engine')->default('google');
            $table->string('device')->default('desktop');
            $table->string('location')->nullable();
            $table->timestamps();

            $table->index(['seo_keyword_id', 'tracked_at']);
            $table->index(['tracked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_seo_keyword_positions');
    }
};
