<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_intake_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('session_id')->constrained('brands_intake_sessions')->onDelete('cascade');
            $table->foreignId('board_block_id')->constrained('brands_intake_board_blocks')->onDelete('cascade');
            $table->foreignId('block_definition_id')->constrained('brands_intake_block_definitions')->onDelete('cascade');
            $table->json('answers')->nullable();
            $table->json('ai_interpretation')->nullable();
            $table->decimal('ai_confidence', 3, 2)->default(0.00);
            $table->json('ai_suggestions')->nullable();
            $table->boolean('user_clarification_needed')->default(false);
            $table->json('conversation_context')->nullable();
            $table->integer('message_count')->default(0);
            $table->integer('clarification_attempts')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'is_completed'], 'brands_ist_session_completed_idx');
            $table->index('board_block_id', 'brands_ist_board_block_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_intake_steps');
    }
};
