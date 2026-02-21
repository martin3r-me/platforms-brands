<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_seo_keyword_competitors', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('seo_keyword_id')->constrained('brands_seo_keywords')->onDelete('cascade');
            $table->string('domain');
            $table->string('url')->nullable();
            $table->integer('position')->nullable();
            $table->timestamp('tracked_at')->nullable();
            $table->timestamps();

            $table->index(['seo_keyword_id', 'domain']);
            $table->index(['domain']);
            $table->index(['tracked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_seo_keyword_competitors');
    }
};
