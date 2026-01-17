<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_facebook_posts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('facebook_page_id')->constrained('brands_facebook_pages')->onDelete('cascade');
            $table->string('external_id'); // Facebook Post ID
            $table->text('message')->nullable(); // Post-Text
            $table->text('story')->nullable(); // Story-Text
            $table->string('type')->nullable(); // Post-Typ (photo, video, status, etc.)
            $table->text('media_url')->nullable(); // URL für Bild oder Video
            $table->text('permalink_url')->nullable(); // Permalink zum Post
            $table->timestamp('published_at')->nullable(); // Veröffentlichungszeit
            $table->timestamp('scheduled_publish_time')->nullable(); // Geplante Veröffentlichungszeit
            $table->string('status')->nullable(); // Status: draft, published, failed
            $table->integer('like_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['facebook_page_id']);
            $table->index(['external_id']);
            $table->index(['team_id']);
            $table->index(['published_at']);
            $table->index(['status']);
            $table->unique(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_facebook_posts');
    }
};
