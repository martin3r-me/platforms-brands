<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('brands_social_platform_format_personas');
        Schema::create('brands_social_platform_format_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_format_id');
            $table->foreign('platform_format_id', 'fk_format_personas_format')->references('id')->on('brands_social_platform_formats')->onDelete('cascade');
            $table->foreignId('persona_id')->constrained('brands_personas')->onDelete('cascade');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['platform_format_id', 'persona_id'], 'format_persona_unique');
            $table->index(['platform_format_id']);
            $table->index(['persona_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_social_platform_format_personas');
    }
};
