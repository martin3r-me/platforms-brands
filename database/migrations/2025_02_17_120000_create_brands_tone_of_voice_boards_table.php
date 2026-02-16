<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_tone_of_voice_boards', function (Blueprint $table) {
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

        // Messaging-Einträge: Slogan, Elevator Pitch, Kernbotschaft, Wert, Claim
        Schema::create('brands_tone_of_voice_entries', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('tone_of_voice_board_id')->constrained('brands_tone_of_voice_boards')->onDelete('cascade');
            $table->string('name'); // z.B. "Haupt-Slogan", "Elevator Pitch Q1"
            $table->string('type'); // slogan, elevator_pitch, core_message, value, claim
            $table->text('content'); // Der eigentliche Text/Inhalt
            $table->text('description')->nullable(); // Kontext, Erklärung
            $table->text('example_positive')->nullable(); // "So ja" Beispieltext
            $table->text('example_negative')->nullable(); // "So nein" Beispieltext
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['tone_of_voice_board_id', 'order']);
            $table->index(['type']);
        });

        // Tone-Dimensionen: formell ↔ locker, ernst ↔ humorvoll, technisch ↔ einfach
        Schema::create('brands_tone_of_voice_dimensions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('tone_of_voice_board_id')->constrained('brands_tone_of_voice_boards')->onDelete('cascade');
            $table->string('name'); // z.B. "Formalität"
            $table->string('label_left'); // z.B. "Formell"
            $table->string('label_right'); // z.B. "Locker"
            $table->integer('value')->default(50); // 0-100, 50 = Mitte
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['tone_of_voice_board_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_tone_of_voice_dimensions');
        Schema::dropIfExists('brands_tone_of_voice_entries');
        Schema::dropIfExists('brands_tone_of_voice_boards');
    }
};
