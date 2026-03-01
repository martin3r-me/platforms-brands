<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_content_brief_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_brief_id')->constrained('brands_content_brief_boards')->onDelete('cascade');
            $table->string('note_type'); // instruction, source, constraint, example, avoid
            $table->text('content');
            $table->unsignedInteger('order')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->timestamps();

            $table->index(['content_brief_id', 'note_type']);
            $table->index(['content_brief_id', 'order']);
            $table->index(['content_brief_id']);
            $table->index(['team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_content_brief_notes');
    }
};
