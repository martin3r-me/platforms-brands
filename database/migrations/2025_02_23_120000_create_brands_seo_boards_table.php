<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_seo_boards', function (Blueprint $table) {
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
            $table->integer('budget_limit_cents')->nullable();
            $table->integer('budget_spent_cents')->default(0);
            $table->timestamp('budget_reset_at')->nullable();
            $table->integer('refresh_interval_days')->default(30);
            $table->timestamp('last_refreshed_at')->nullable();
            $table->json('dataforseo_config')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'order']);
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_seo_boards');
    }
};
