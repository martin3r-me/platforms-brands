<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_seo_keyword_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('seo_board_id')->constrained('brands_seo_boards')->onDelete('cascade');
            $table->string('name');
            $table->string('color')->nullable();
            $table->integer('order')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_seo_keyword_clusters');
    }
};
