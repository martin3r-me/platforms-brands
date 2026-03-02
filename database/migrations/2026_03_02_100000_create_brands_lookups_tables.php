<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands_lookups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name'); // slug: content_type, search_intent, status
            $table->string('label');
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });

        Schema::create('brands_lookup_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lookup_id')->constrained('brands_lookups')->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['lookup_id', 'value']);
        });

        // target_url auf Content Brief Boards
        Schema::table('brands_content_brief_boards', function (Blueprint $table) {
            $table->string('target_url')->nullable()->after('target_slug');
        });
    }

    public function down(): void
    {
        Schema::table('brands_content_brief_boards', function (Blueprint $table) {
            $table->dropColumn('target_url');
        });

        Schema::dropIfExists('brands_lookup_values');
        Schema::dropIfExists('brands_lookups');
    }
};
