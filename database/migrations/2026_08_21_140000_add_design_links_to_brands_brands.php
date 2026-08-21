<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wireframe- und Mockup-Link pro Marke.
 *
 * Zwei einzelne Design-Pointer (i.d.R. Figma o.ä.) für die Erstentwurf-Phase –
 * das eigene Gegenstück zum Referenzen-Board (fremde Inspiration). TEXT, weil
 * Design-Tool-URLs mit Query-Parametern häufig lang sind.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('brands_brands')) {
            return;
        }

        Schema::table('brands_brands', function (Blueprint $table) {
            if (!Schema::hasColumn('brands_brands', 'wireframe_url')) {
                $table->text('wireframe_url')->nullable()->after('description');
            }
            if (!Schema::hasColumn('brands_brands', 'mockup_url')) {
                $table->text('mockup_url')->nullable()->after('wireframe_url');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('brands_brands')) {
            return;
        }

        Schema::table('brands_brands', function (Blueprint $table) {
            $table->dropColumn(['wireframe_url', 'mockup_url']);
        });
    }
};
