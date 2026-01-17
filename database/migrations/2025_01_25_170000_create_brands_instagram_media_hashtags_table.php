<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_instagram_media_hashtags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_media_id')->constrained('brands_instagram_media')->onDelete('cascade');
            $table->foreignId('hashtag_id')->constrained('brands_instagram_hashtags')->onDelete('cascade');
            $table->integer('count')->default(1); // Wie oft der Hashtag in diesem Media vorkommt
            $table->timestamps();
            
            $table->index(['instagram_media_id']);
            $table->index(['hashtag_id']);
            $table->unique(['instagram_media_id', 'hashtag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_instagram_media_hashtags');
    }
};
