<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_seo_keyword_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('seo_keyword_id')->constrained('brands_seo_keywords')->onDelete('cascade');
            $table->string('context_type');
            $table->unsignedBigInteger('context_id');
            $table->string('label')->nullable();
            $table->string('url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['seo_keyword_id']);
            $table->index(['context_type', 'context_id']);
            $table->unique(['seo_keyword_id', 'context_type', 'context_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_seo_keyword_contexts');
    }
};
