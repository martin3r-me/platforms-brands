<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guideline Board – Container für Markenregeln
        if (!Schema::hasTable('brands_guideline_boards')) {
            Schema::create('brands_guideline_boards', function (Blueprint $table) {
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

                $table->timestamps();

                $table->index(['brand_id', 'order']);
                $table->index(['team_id']);
            });
        }

        // Guideline-Kapitel – Wiki-artige Struktur mit Kapiteln
        if (!Schema::hasTable('brands_guideline_chapters')) {
            Schema::create('brands_guideline_chapters', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('guideline_board_id')->constrained('brands_guideline_boards')->onDelete('cascade');
                $table->string('title'); // z.B. "Logo-Verwendung", "Farbrichtlinien", "Typografie-Regeln"
                $table->text('description')->nullable();
                $table->string('icon')->nullable(); // Heroicon-Name, z.B. "heroicon-o-paint-brush"
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->index(['guideline_board_id', 'order'], 'gl_chapters_board_order_idx');
            });
        }

        // Guideline-Einträge – Einzelne Regeln mit Do/Don't
        if (!Schema::hasTable('brands_guideline_entries')) {
            Schema::create('brands_guideline_entries', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('guideline_chapter_id')->constrained('brands_guideline_chapters')->onDelete('cascade');
                $table->string('title'); // z.B. "Mindestgröße des Logos"
                $table->text('rule_text'); // Die eigentliche Regel/Richtlinie
                $table->text('rationale')->nullable(); // Begründung warum diese Regel existiert
                $table->text('do_example')->nullable(); // Positives Beispiel (Text + optional Bild)
                $table->text('dont_example')->nullable(); // Negatives Beispiel (Text + optional Bild)
                $table->string('do_image_path')->nullable(); // Optionaler Bildpfad für Do-Beispiel
                $table->string('dont_image_path')->nullable(); // Optionaler Bildpfad für Don't-Beispiel
                $table->json('cross_references')->nullable(); // Links zu anderen Boards: [{board_type, board_id, label}]
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->index(['guideline_chapter_id', 'order'], 'gl_entries_chapter_order_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_guideline_entries');
        Schema::dropIfExists('brands_guideline_chapters');
        Schema::dropIfExists('brands_guideline_boards');
    }
};
