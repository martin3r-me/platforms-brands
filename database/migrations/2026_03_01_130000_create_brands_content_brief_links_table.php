<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_content_brief_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_content_brief_id')->constrained('brands_content_brief_boards')->onDelete('cascade');
            $table->foreignId('target_content_brief_id')->constrained('brands_content_brief_boards')->onDelete('cascade');
            $table->string('link_type');
            $table->string('anchor_hint')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['source_content_brief_id', 'target_content_brief_id', 'link_type'], 'brands_content_brief_links_unique');
            $table->index(['source_content_brief_id']);
            $table->index(['target_content_brief_id']);
            $table->index(['link_type']);
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_content_brief_links');
    }
};
