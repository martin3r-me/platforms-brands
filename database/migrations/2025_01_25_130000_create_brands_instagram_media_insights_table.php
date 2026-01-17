<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_instagram_media_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_media_id')->constrained('brands_instagram_media')->onDelete('cascade');
            $table->date('insight_date');
            
            // Basis-Metriken
            $table->integer('impressions')->nullable();
            $table->integer('reach')->nullable();
            $table->integer('saved')->nullable();
            $table->integer('comments')->nullable();
            $table->integer('likes')->nullable();
            $table->integer('shares')->nullable();
            $table->integer('total_interactions')->nullable();
            
            // Story-spezifische Metriken
            $table->integer('replies')->nullable();
            $table->json('navigation')->nullable(); // Story Navigation Actions
            
            // Reel-spezifische Metriken
            $table->integer('plays')->nullable();
            $table->integer('clips_replays_count')->nullable();
            $table->integer('ig_reels_aggregated_all_plays_count')->nullable();
            $table->integer('ig_reels_avg_watch_time')->nullable();
            $table->integer('ig_reels_video_view_total_time')->nullable();
            
            $table->timestamps();
            
            $table->index(['instagram_media_id', 'insight_date']);
            $table->unique(['instagram_media_id', 'insight_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_instagram_media_insights');
    }
};
