<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('brands_social_card_contracts');
        Schema::create('brands_social_card_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('social_card_id')->constrained('brands_social_cards')->onDelete('cascade');
            $table->foreignId('platform_format_id');
            $table->foreign('platform_format_id', 'fk_card_contracts_format')->references('id')->on('brands_social_platform_formats')->onDelete('cascade');
            $table->json('contract')->nullable();
            $table->string('status')->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->string('external_post_id')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();

            $table->unique(['social_card_id', 'platform_format_id'], 'card_format_unique');
            $table->index(['social_card_id']);
            $table->index(['platform_format_id']);
            $table->index(['status']);
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_social_card_contracts');
    }
};
