<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_seo_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('seo_board_id')->constrained('brands_seo_boards')->onDelete('cascade');
            $table->foreignId('keyword_cluster_id')->nullable()->constrained('brands_seo_keyword_clusters')->nullOnDelete();
            $table->string('keyword');
            $table->integer('search_volume')->nullable();
            $table->tinyInteger('keyword_difficulty')->nullable();
            $table->integer('cpc_cents')->nullable();
            $table->string('trend')->nullable();
            $table->string('search_intent')->nullable();
            $table->string('keyword_type')->nullable();
            $table->text('content_idea')->nullable();
            $table->string('priority')->nullable();
            $table->string('url')->nullable();
            $table->integer('position')->nullable();
            $table->text('notes')->nullable();
            $table->integer('order')->default(0);
            $table->timestamp('last_fetched_at')->nullable();
            $table->json('dataforseo_raw')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();

            $table->index(['seo_board_id', 'keyword_cluster_id']);
            $table->index(['keyword']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_seo_keywords');
    }
};
