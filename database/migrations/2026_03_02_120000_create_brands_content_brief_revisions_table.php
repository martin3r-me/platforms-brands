<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_content_brief_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('content_brief_board_id')->constrained('brands_content_brief_boards')->cascadeOnDelete();
            $table->string('revision_type')->default('optimization'); // per Lookup validiert
            $table->text('summary'); // Was wurde geändert? (Freitext / LLM-generiert)
            $table->json('metrics_before')->nullable(); // {word_count, h2_count, h3_count, h4_count, paragraph_count, image_count, internal_link_count, external_link_count}
            $table->json('metrics_after')->nullable();  // gleiche Struktur
            $table->json('changes')->nullable(); // [{type: "added_h2", detail: "..."}, {type: "rewritten_paragraph", detail: "..."}]
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revised_at');
            $table->timestamps();

            $table->index(['content_brief_board_id', 'revised_at']);
            $table->index(['revised_at']);
            $table->index(['revision_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_content_brief_revisions');
    }
};
