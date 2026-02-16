<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_typography_boards', function (Blueprint $table) {
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

        Schema::create('brands_typography_entries', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('typography_board_id')->constrained('brands_typography_boards')->onDelete('cascade');
            $table->string('name'); // z.B. "Headline 1", "Body Text", "Caption"
            $table->string('role')->nullable(); // h1, h2, h3, h4, h5, h6, body, caption, overline, subtitle
            $table->string('font_family'); // z.B. "Inter", "Roboto", "Open Sans"
            $table->string('font_source')->default('system'); // system, google, custom
            $table->string('font_file_path')->nullable(); // Pfad zur hochgeladenen Schriftdatei
            $table->string('font_file_name')->nullable(); // Original-Dateiname
            $table->integer('font_weight')->default(400); // 100-900
            $table->string('font_style')->default('normal'); // normal, italic
            $table->decimal('font_size', 8, 2)->default(16); // in px
            $table->decimal('line_height', 8, 2)->nullable(); // z.B. 1.5 oder 24 (px)
            $table->decimal('letter_spacing', 8, 2)->nullable(); // in px oder em
            $table->string('text_transform')->nullable(); // uppercase, lowercase, capitalize, none
            $table->text('sample_text')->nullable(); // Beispieltext für Vorschau
            $table->integer('order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['typography_board_id', 'order']);
            $table->index(['role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_typography_entries');
        Schema::dropIfExists('brands_typography_boards');
    }
};
