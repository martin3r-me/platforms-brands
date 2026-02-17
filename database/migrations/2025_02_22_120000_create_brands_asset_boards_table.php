<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asset Board – Container für Marken-Assets und Templates
        if (!Schema::hasTable('brands_asset_boards')) {
            Schema::create('brands_asset_boards', function (Blueprint $table) {
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
        }

        // Assets – Einzelne Assets im Asset Board
        if (!Schema::hasTable('brands_assets')) {
            Schema::create('brands_assets', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('asset_board_id')->constrained('brands_asset_boards')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('asset_type', ['sm_template', 'letterhead', 'signature', 'banner', 'presentation', 'other'])->default('other');
                $table->string('file_path'); // Pfad zur aktuellen Datei
                $table->string('file_name')->nullable(); // Originaler Dateiname
                $table->string('mime_type')->nullable(); // MIME-Type der Datei
                $table->unsignedBigInteger('file_size')->nullable(); // Dateigröße in Bytes
                $table->string('thumbnail_path')->nullable(); // Pfad zum Thumbnail
                $table->json('tags')->nullable(); // Kanal-Tags: ["Instagram", "LinkedIn", "Print", "Web"]
                $table->json('available_formats')->nullable(); // Verfügbare Download-Formate: ["png", "svg", "pdf"]
                $table->integer('current_version')->default(1);
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->index(['asset_board_id', 'order'], 'assets_board_order_idx');
                $table->index(['asset_board_id', 'asset_type'], 'assets_board_type_idx');
            });
        }

        // Asset Versions – Versionierung für Assets
        if (!Schema::hasTable('brands_asset_versions')) {
            Schema::create('brands_asset_versions', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->foreignId('asset_id')->constrained('brands_assets')->onDelete('cascade');
                $table->integer('version_number');
                $table->string('file_path'); // Pfad zur versionierten Datei
                $table->string('file_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->text('change_note')->nullable(); // Was hat sich geändert
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                $table->index(['asset_id', 'version_number'], 'asset_versions_asset_version_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_asset_versions');
        Schema::dropIfExists('brands_assets');
        Schema::dropIfExists('brands_asset_boards');
    }
};
