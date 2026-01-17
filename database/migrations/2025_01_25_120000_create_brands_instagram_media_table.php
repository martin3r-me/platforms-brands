<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_instagram_media', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('instagram_account_id')->constrained('brands_instagram_accounts')->onDelete('cascade');
            $table->string('external_id'); // Instagram Media ID
            $table->text('caption')->nullable();
            $table->string('media_type'); // IMAGE, VIDEO, CAROUSEL_ALBUM, STORY, REEL
            $table->text('media_url')->nullable();
            $table->text('permalink')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->integer('like_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->boolean('is_story')->default(false);
            $table->boolean('insights_available')->default(true);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['instagram_account_id']);
            $table->index(['external_id']);
            $table->index(['team_id']);
            $table->index(['timestamp']);
            $table->index(['is_story']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_instagram_media');
    }
};
