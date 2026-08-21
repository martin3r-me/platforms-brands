<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('brands_reference_boards')) {
            Schema::create('brands_reference_boards', function (Blueprint $table) {
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

        // Referenzen: bewertete Domains (Website-Benchmarks) mit Begründung
        if (!Schema::hasTable('brands_references')) {
            Schema::create('brands_references', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('reference_board_id')->constrained('brands_reference_boards')->onDelete('cascade');
                $table->string('url');
                $table->string('title')->nullable();          // Seitentitel / Label
                $table->string('screenshot_url')->nullable();  // OG-Image / Screenshot
                $table->string('verdict')->default('like');    // like | dislike | neutral
                $table->text('reason')->nullable();            // Warum (das Herzstück)
                $table->json('aspects')->nullable();           // ['layout','typography','color',…]
                $table->string('industry')->nullable();        // optionale Branche
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->index(['reference_board_id', 'order'], 'references_board_order_idx');
                $table->index(['verdict']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_references');
        Schema::dropIfExists('brands_reference_boards');
    }
};
