<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands_content_boards', function (Blueprint $table) {
            $table->foreignId('multi_content_board_slot_id')
                ->nullable()
                ->after('brand_id')
                ->constrained('brands_multi_content_board_slots')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('brands_content_boards', function (Blueprint $table) {
            $table->dropForeign(['multi_content_board_slot_id']);
            $table->dropColumn('multi_content_board_slot_id');
        });
    }
};
