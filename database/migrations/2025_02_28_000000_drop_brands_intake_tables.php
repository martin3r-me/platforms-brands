<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('brands_intake_steps');
        Schema::dropIfExists('brands_intake_sessions');
        Schema::dropIfExists('brands_intake_board_blocks');
        Schema::dropIfExists('brands_intake_boards');
        Schema::dropIfExists('brands_intake_block_definitions');
    }

    public function down(): void
    {
        // Intentionally left empty — tables are not recreated.
    }
};
