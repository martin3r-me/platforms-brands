<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_instagram_hashtags', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('name'); // Hashtag-Name (mit #)
            $table->string('instagram_hashtag_id')->nullable(); // Instagram Hashtag ID
            $table->integer('usage_count')->default(0); // Wie oft wurde dieser Hashtag verwendet
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['name']);
            $table->index(['team_id']);
            $table->index(['instagram_hashtag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_instagram_hashtags');
    }
};
