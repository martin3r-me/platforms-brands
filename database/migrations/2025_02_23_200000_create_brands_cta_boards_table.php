<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_cta_boards', function (Blueprint $table) {
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
            $table->timestamps();

            $table->index(['brand_id', 'order']);
            $table->index(['team_id']);
        });

        // CTA → CTA Board Zuordnung
        Schema::table('brand_ctas', function (Blueprint $table) {
            $table->foreignId('cta_board_id')->nullable()->after('brand_id')
                ->constrained('brands_cta_boards')->onDelete('set null');
            $table->integer('order')->default(0)->after('is_active');

            $table->index(['cta_board_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::table('brand_ctas', function (Blueprint $table) {
            $table->dropForeign(['cta_board_id']);
            $table->dropIndex(['cta_board_id', 'order']);
            $table->dropColumn(['cta_board_id', 'order']);
        });

        Schema::dropIfExists('brands_cta_boards');
    }
};
