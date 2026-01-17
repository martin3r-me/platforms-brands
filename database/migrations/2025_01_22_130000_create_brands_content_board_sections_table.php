<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_content_board_sections', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('content_board_id')->constrained('brands_content_boards')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            
            $table->timestamps();
            
            $table->index(['content_board_id', 'order']);
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_content_board_sections');
    }
};
