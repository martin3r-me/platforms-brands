<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_instagram_media_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_media_id')->constrained('brands_instagram_media')->onDelete('cascade');
            $table->string('external_id'); // Instagram Comment ID
            $table->text('text')->nullable();
            $table->string('username')->nullable();
            $table->integer('like_count')->default(0);
            $table->timestamp('timestamp')->nullable(); // Instagram Timestamp
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['instagram_media_id']);
            $table->index(['external_id']);
            $table->index(['team_id']);
            $table->unique(['external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_instagram_media_comments');
    }
};
