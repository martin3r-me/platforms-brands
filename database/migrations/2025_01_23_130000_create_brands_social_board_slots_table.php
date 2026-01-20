<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_social_board_slots', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('social_board_id')->constrained('brands_social_boards')->onDelete('cascade');
            $table->string('name');
            $table->integer('order')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_social_board_slots');
    }
};
