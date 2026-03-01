<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_content_brief_boards', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('brand_id')->constrained('brands_brands')->onDelete('cascade');
            $table->foreignId('seo_board_id')->nullable()->constrained('brands_seo_boards')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('content_type')->default('guide');
            $table->string('search_intent')->default('informational');
            $table->string('status')->default('draft');
            $table->string('target_slug')->nullable();
            $table->unsignedInteger('target_word_count')->nullable();
            $table->integer('order')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->boolean('done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'order']);
            $table->index(['brand_id', 'status']);
            $table->index(['seo_board_id']);
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_content_brief_boards');
    }
};
