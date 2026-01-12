<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_ci_board_colors', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('brand_ci_board_id')->constrained('brands_ci_boards')->onDelete('cascade');
            $table->string('title');
            $table->string('color')->nullable(); // Hex-Farbwert
            $table->integer('order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['brand_ci_board_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_ci_board_colors');
    }
};
