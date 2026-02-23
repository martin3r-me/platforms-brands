<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_intake_board_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('intake_board_id')->constrained('brands_intake_boards')->onDelete('cascade');
            $table->foreignId('block_definition_id')->constrained('brands_intake_block_definitions')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();

            $table->index(['intake_board_id', 'sort_order'], 'brands_ibb_board_sort_idx');
            $table->unique(['intake_board_id', 'block_definition_id'], 'brands_ibb_board_block_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_intake_board_blocks');
    }
};
