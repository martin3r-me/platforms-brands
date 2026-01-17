<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_meta_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('brand_id')->constrained('brands_brands')->onDelete('cascade');
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('token_type')->nullable()->default('Bearer');
            $table->json('scopes')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['brand_id']);
            $table->index(['team_id']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_meta_tokens');
    }
};
