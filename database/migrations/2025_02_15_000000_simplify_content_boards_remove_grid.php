<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add content_board_id to blocks table (nullable initially for migration)
        Schema::table('brands_content_board_blocks', function (Blueprint $table) {
            $table->unsignedBigInteger('content_board_id')->nullable()->after('uuid');
        });

        // Step 2: Migrate existing blocks - resolve content_board_id via row → section → content_board
        DB::statement('
            UPDATE brands_content_board_blocks AS b
            JOIN brands_content_board_rows AS r ON b.row_id = r.id
            JOIN brands_content_board_sections AS s ON r.section_id = s.id
            SET b.content_board_id = s.content_board_id
        ');

        // Step 3: Re-calculate order for blocks within each content board (flatten the hierarchy)
        $contentBoardIds = DB::table('brands_content_board_blocks')
            ->whereNotNull('content_board_id')
            ->distinct()
            ->pluck('content_board_id');

        foreach ($contentBoardIds as $contentBoardId) {
            $blocks = DB::table('brands_content_board_blocks')
                ->where('content_board_id', $contentBoardId)
                ->join('brands_content_board_rows', 'brands_content_board_blocks.row_id', '=', 'brands_content_board_rows.id')
                ->join('brands_content_board_sections', 'brands_content_board_rows.section_id', '=', 'brands_content_board_sections.id')
                ->orderBy('brands_content_board_sections.order')
                ->orderBy('brands_content_board_rows.order')
                ->orderBy('brands_content_board_blocks.order')
                ->select('brands_content_board_blocks.id')
                ->get();

            foreach ($blocks as $index => $block) {
                DB::table('brands_content_board_blocks')
                    ->where('id', $block->id)
                    ->update(['order' => $index + 1]);
            }
        }

        // Step 4: Now make content_board_id required and add foreign key
        Schema::table('brands_content_board_blocks', function (Blueprint $table) {
            // Drop old foreign key and index for row_id
            $table->dropForeign(['row_id']);
            $table->dropIndex(['row_id', 'order']);

            // Make content_board_id non-nullable and add foreign key
            $table->unsignedBigInteger('content_board_id')->nullable(false)->change();
            $table->foreign('content_board_id')->references('id')->on('brands_content_boards')->onDelete('cascade');

            // Drop row_id and span columns
            $table->dropColumn(['row_id', 'span']);

            // Add new index
            $table->index(['content_board_id', 'order']);
        });

        // Step 5: Drop sections and rows tables (data has been migrated)
        Schema::dropIfExists('brands_content_board_rows');
        Schema::dropIfExists('brands_content_board_sections');
    }

    public function down(): void
    {
        // Re-create sections table
        Schema::create('brands_content_board_sections', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('content_board_id')->constrained('brands_content_boards')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            $table->index(['content_board_id', 'order']);
            $table->index(['team_id']);
        });

        // Re-create rows table
        Schema::create('brands_content_board_rows', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('section_id')->constrained('brands_content_board_sections')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();
            $table->index(['section_id', 'order']);
            $table->index(['team_id']);
        });

        // Restore blocks table structure
        Schema::table('brands_content_board_blocks', function (Blueprint $table) {
            $table->dropForeign(['content_board_id']);
            $table->dropIndex(['content_board_id', 'order']);
            $table->dropColumn('content_board_id');

            $table->unsignedBigInteger('row_id')->after('uuid');
            $table->tinyInteger('span')->unsigned()->default(1)->after('order');

            $table->foreign('row_id')->references('id')->on('brands_content_board_rows')->onDelete('cascade');
            $table->index(['row_id', 'order']);
        });
    }
};
