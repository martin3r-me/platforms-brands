<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bestandsseite (aktuelle Live-Website) pro Marke.
 *
 * Der IST-Zustand als Ausgangspunkt des Relaunches – bildet mit wireframe_url
 * (SOLL-Struktur) und mockup_url (SOLL-Design) die Relaunch-Trias.
 * TEXT wegen potenziell langer URLs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('brands_brands')) {
            return;
        }

        Schema::table('brands_brands', function (Blueprint $table) {
            if (!Schema::hasColumn('brands_brands', 'live_url')) {
                $table->text('live_url')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('brands_brands')) {
            return;
        }

        Schema::table('brands_brands', function (Blueprint $table) {
            $table->dropColumn('live_url');
        });
    }
};
