<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('brands_persona_boards')) {
            Schema::create('brands_persona_boards', function (Blueprint $table) {
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

        // Personas: Einzelne Zielgruppen-Personas mit strukturierten Feldern
        if (!Schema::hasTable('brands_personas')) {
            Schema::create('brands_personas', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('persona_board_id')->constrained('brands_persona_boards')->onDelete('cascade');
                $table->string('name'); // Persona-Name, z.B. "Marketing-Maria"
                $table->string('avatar_url')->nullable(); // Avatar-Bild URL
                $table->integer('age')->nullable(); // Alter
                $table->string('gender')->nullable(); // Geschlecht
                $table->string('occupation')->nullable(); // Beruf
                $table->string('location')->nullable(); // Wohnort
                $table->string('education')->nullable(); // Bildung
                $table->string('income_range')->nullable(); // Einkommensbereich
                $table->text('bio')->nullable(); // Kurzbeschreibung / Bio
                $table->json('pain_points')->nullable(); // Pain Points als Array [{text: string}]
                $table->json('goals')->nullable(); // Ziele als Array [{text: string}]
                $table->json('quotes')->nullable(); // Typische Zitate als Array [{text: string}]
                $table->json('behaviors')->nullable(); // Verhalten/Gewohnheiten als Array [{text: string}]
                $table->json('channels')->nullable(); // Bevorzugte Kanäle als Array [{text: string}]
                $table->json('brands_liked')->nullable(); // Marken die sie mag als Array [{text: string}]
                $table->foreignId('tone_of_voice_board_id')->nullable()->constrained('brands_tone_of_voice_boards')->onDelete('set null'); // Verknüpfung: Persona → Tone of Voice
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->index(['persona_board_id', 'order'], 'personas_board_order_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_personas');
        Schema::dropIfExists('brands_persona_boards');
    }
};
