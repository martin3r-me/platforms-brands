<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_content_board_rows', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('section_id')->constrained('brands_content_board_sections')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            
            $table->timestamps();
            
            $table->index(['section_id', 'order']);
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_content_board_rows');
    }
};
