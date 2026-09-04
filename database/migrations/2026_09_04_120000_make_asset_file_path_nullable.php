<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * file_path auf den Asset-Tabellen nullable machen.
 *
 * Assets laufen jetzt über ContextFiles (wie Logo/Moodboard); file_path wird
 * bei neuen Uploads nicht mehr gesetzt. Die Spalte war NOT NULL ohne Default
 * ("Field 'file_path' doesn't have a default value") — nullable behebt das.
 * Legacy-Daten mit gesetztem file_path bleiben unverändert (Fallback im Model).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('brands_assets', 'file_path')) {
            Schema::table('brands_assets', function (Blueprint $table) {
                $table->string('file_path')->nullable()->change();
            });
        }

        if (Schema::hasColumn('brands_asset_versions', 'file_path')) {
            Schema::table('brands_asset_versions', function (Blueprint $table) {
                $table->string('file_path')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Bewusst kein Rückbau auf NOT NULL: neue ContextFile-Assets haben
        // kein file_path, ein NOT NULL würde sie brechen.
    }
};
