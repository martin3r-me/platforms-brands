<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Moodboard Board – Container für Bildsprache/Moodboard
        if (!Schema::hasTable('brands_moodboard_boards')) {
            Schema::create('brands_moodboard_boards', function (Blueprint $table) {
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

        // Moodboard Images – Einzelne Bilder im Moodboard mit Tags und Annotationen
        if (!Schema::hasTable('brands_moodboard_images')) {
            Schema::create('brands_moodboard_images', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('moodboard_board_id')->constrained('brands_moodboard_boards')->onDelete('cascade');
                $table->string('title')->nullable();
                $table->string('image_path'); // Pfad zum hochgeladenen Bild
                $table->text('annotation')->nullable(); // Warum passt das Bild zur Marke
                $table->json('tags')->nullable(); // Kategorien: ["Produkt", "Lifestyle", "People", "Texture"]
                $table->enum('type', ['do', 'dont'])->default('do'); // Passend vs. unpassend
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->index(['moodboard_board_id', 'order'], 'mb_images_board_order_idx');
                $table->index(['moodboard_board_id', 'type'], 'mb_images_board_type_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_moodboard_images');
        Schema::dropIfExists('brands_moodboard_boards');
    }
};
