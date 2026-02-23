<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_intake_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('session_token', 64)->unique();
            $table->foreignId('intake_board_id')->constrained('brands_intake_boards')->onDelete('cascade');
            $table->string('status')->default('started');
            $table->json('answers')->nullable();
            $table->string('respondent_name')->nullable();
            $table->string('respondent_email')->nullable();
            $table->integer('current_step')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['intake_board_id', 'status'], 'brands_is_board_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_intake_sessions');
    }
};
