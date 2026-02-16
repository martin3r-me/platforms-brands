<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_logo_boards', function (Blueprint $table) {
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

        Schema::create('brands_logo_variants', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('logo_board_id')->constrained('brands_logo_boards')->onDelete('cascade');
            $table->string('name'); // z.B. "Primary Logo", "Secondary Logo", "Favicon"
            $table->string('type'); // primary, secondary, monochrome, favicon, icon, wordmark, pictorial_mark, combination_mark
            $table->text('description')->nullable();
            $table->text('usage_guidelines')->nullable(); // Verwendungsrichtlinien

            // Dateien (Haupt-Vorschau)
            $table->string('file_path')->nullable(); // Pfad zur Hauptdatei (SVG bevorzugt)
            $table->string('file_name')->nullable(); // Original-Dateiname
            $table->string('file_format')->nullable(); // svg, png, pdf, ai, eps

            // Zusätzliche Formate als JSON
            $table->json('additional_formats')->nullable(); // [{file_path, file_name, format, width, height}]

            // Schutzzonen & Mindestgrößen
            $table->decimal('clearspace_factor', 8, 2)->nullable(); // Schutzzone als Faktor der Logo-Höhe (z.B. 0.5 = halbe Logohöhe)
            $table->integer('min_width_px')->nullable(); // Mindestbreite in px (digital)
            $table->integer('min_width_mm')->nullable(); // Mindestbreite in mm (print)
            $table->string('background_color')->nullable(); // Bevorzugte Hintergrundfarbe (hex)

            // Do's & Don'ts
            $table->json('dos')->nullable(); // [{text, image_path}] - Richtige Verwendung
            $table->json('donts')->nullable(); // [{text, image_path}] - Falsche Verwendung

            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['logo_board_id', 'order']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_logo_variants');
        Schema::dropIfExists('brands_logo_boards');
    }
};
