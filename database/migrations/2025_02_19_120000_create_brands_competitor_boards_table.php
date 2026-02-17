<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('brands_competitor_boards')) {
            Schema::create('brands_competitor_boards', function (Blueprint $table) {
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

                // Positionierungsmatrix Achsen-Konfiguration
                $table->string('axis_x_label')->default('Preis');
                $table->string('axis_y_label')->default('Qualität');
                $table->string('axis_x_min_label')->default('Niedrig');
                $table->string('axis_x_max_label')->default('Hoch');
                $table->string('axis_y_min_label')->default('Niedrig');
                $table->string('axis_y_max_label')->default('Hoch');

                $table->timestamps();

                $table->index(['brand_id', 'order']);
                $table->index(['team_id']);
            });
        }

        // Wettbewerber: Einzelne Wettbewerber mit Profil und Analyse
        if (!Schema::hasTable('brands_competitors')) {
            Schema::create('brands_competitors', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('competitor_board_id')->constrained('brands_competitor_boards')->onDelete('cascade');
                $table->string('name'); // Wettbewerber-Name
                $table->string('logo_url')->nullable(); // Logo-URL
                $table->string('website_url')->nullable(); // Website-URL
                $table->text('description')->nullable(); // Kurzbeschreibung
                $table->json('strengths')->nullable(); // Stärken als Array [{text: string}]
                $table->json('weaknesses')->nullable(); // Schwächen als Array [{text: string}]
                $table->text('notes')->nullable(); // Freitext-Notizen

                // Positionierung auf der Matrix (0-100 Prozent)
                $table->integer('position_x')->nullable(); // X-Position (0-100)
                $table->integer('position_y')->nullable(); // Y-Position (0-100)
                $table->boolean('is_own_brand')->default(false); // Eigene Marke markieren

                // Differenzierungsmerkmale für Tabelle
                $table->json('differentiation')->nullable(); // [{category: string, own_value: string, competitor_value: string}]

                $table->integer('order')->default(0);
                $table->timestamps();

                $table->index(['competitor_board_id', 'order'], 'competitors_board_order_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_competitors');
        Schema::dropIfExists('brands_competitor_boards');
    }
};
