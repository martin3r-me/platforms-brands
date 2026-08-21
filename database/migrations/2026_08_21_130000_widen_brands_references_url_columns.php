<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * url + screenshot_url auf TEXT verbreitern.
 *
 * Reale OG-Image-/CDN-URLs (mit Signaturen/Query-Parametern) überschreiten
 * häufig 255 Zeichen. Als VARCHAR(255) führte das beim Speichern zu einem
 * stillen Insert-Fehler ("Data too long") ohne Feedback im UI. TEXT beseitigt
 * diese Fehlerklasse; die Validierung begrenzt url weiterhin auf 2048.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('brands_references')) {
            return;
        }

        Schema::table('brands_references', function (Blueprint $table) {
            $table->text('url')->change();
            $table->text('screenshot_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('brands_references')) {
            return;
        }

        Schema::table('brands_references', function (Blueprint $table) {
            $table->string('url')->change();
            $table->string('screenshot_url')->nullable()->change();
        });
    }
};
