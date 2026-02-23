<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_intake_boards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('brand_id')->constrained('brands_brands')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->string('public_token', 64)->unique()->nullable();
            $table->string('ai_personality')->nullable();
            $table->string('industry_context')->nullable();
            $table->json('ai_instructions')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'order'], 'brands_ib_brand_order_idx');
            $table->index(['team_id', 'status'], 'brands_ib_team_status_idx');
            $table->index('public_token', 'brands_ib_public_token_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_intake_boards');
    }
};
