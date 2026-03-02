<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_content_brief_rankings', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('content_brief_board_id')->constrained('brands_content_brief_boards')->cascadeOnDelete();
            $table->foreignId('seo_keyword_id')->constrained('brands_seo_keywords')->cascadeOnDelete();
            $table->integer('position')->nullable(); // null = nicht gefunden
            $table->integer('previous_position')->nullable();
            $table->string('target_url'); // die URL die wir geprüft haben
            $table->string('found_url')->nullable(); // die URL die tatsächlich rankt
            $table->boolean('is_target_match')->default(false); // target_url == found_url?
            $table->json('serp_features')->nullable(); // Top-10 Domains im SERP
            $table->integer('cost_cents')->default(0);
            $table->string('search_engine')->default('google');
            $table->string('device')->default('desktop');
            $table->string('location')->nullable();
            $table->timestamp('tracked_at');
            $table->timestamps();

            $table->index(['content_brief_board_id', 'tracked_at']);
            $table->index(['seo_keyword_id', 'tracked_at']);
            $table->index(['tracked_at']);
            $table->index(['is_target_match']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_content_brief_rankings');
    }
};
