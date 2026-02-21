<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_ctas', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('brand_id')->constrained('brands_brands')->onDelete('cascade');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('type'); // primary, secondary, micro
            $table->string('funnel_stage'); // awareness, consideration, decision
            $table->foreignId('target_page_id')->nullable()->constrained('brands_content_board_blocks')->nullOnDelete();
            $table->string('target_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();

            $table->index(['brand_id', 'type']);
            $table->index(['brand_id', 'funnel_stage']);
            $table->index(['target_page_id']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_ctas');
    }
};
