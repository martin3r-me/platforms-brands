<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_ci_boards', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('brand_id')->constrained('brands_brands')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->boolean('done')->default(false);
            $table->timestamp('done_at')->nullable();
            
            // CiBoard-spezifische Felder
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('accent_color')->nullable();
            $table->text('slogan')->nullable();
            $table->string('font_family')->nullable();
            $table->text('tagline')->nullable();
            
            $table->timestamps();
            
            $table->index(['brand_id', 'order']);
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_ci_boards');
    }
};
