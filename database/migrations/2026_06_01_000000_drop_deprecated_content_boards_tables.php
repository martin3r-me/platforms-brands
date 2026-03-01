<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finale Entfernung der deprecated Content Boards und Multi Content Boards.
 *
 * Ticket #441: Content Boards – Deprecation & Migration
 * Geplantes Ausführungsdatum: 2026-06-01
 *
 * Entfernt folgende Tabellen:
 * - brands_content_board_block_texts (Text-Content für Blocks)
 * - brands_content_board_blocks (Block-Einträge)
 * - brands_content_boards (Content Boards / Pages)
 * - brands_multi_content_board_slots (Kanban-Slots)
 * - brands_multi_content_boards (Multi-Content-Boards)
 *
 * ACHTUNG: Diese Migration ist NICHT umkehrbar (irreversible).
 *          Alle Daten in diesen Tabellen werden unwiderruflich gelöscht.
 *          Vor dem Ausführen sicherstellen, dass keine Daten mehr benötigt werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Reihenfolge beachten: FK-Abhängigkeiten von innen nach außen auflösen

        // 1. Text-Content (polymorphe Beziehung zu Blocks)
        Schema::dropIfExists('brands_content_board_block_texts');

        // 2. Blocks (FK → content_boards)
        Schema::dropIfExists('brands_content_board_blocks');

        // 3. Content Boards (FK → multi_content_board_slots, brands)
        Schema::dropIfExists('brands_content_boards');

        // 4. Multi-Content-Board Slots (FK → multi_content_boards)
        Schema::dropIfExists('brands_multi_content_board_slots');

        // 5. Multi-Content-Boards (FK → brands)
        Schema::dropIfExists('brands_multi_content_boards');
    }

    public function down(): void
    {
        // Irreversible Migration: Tabellen und Daten können nicht wiederhergestellt werden.
        // Bei Bedarf die originalen Create-Migrations erneut ausführen:
        // - 2025_01_22_120000_create_brands_content_boards_table.php
        // - 2025_01_22_132000_create_brands_content_board_blocks_table.php
        // - 2025_01_27_120000_create_brands_multi_content_boards_table.php
        // - 2025_01_27_130000_create_brands_multi_content_board_slots_table.php
        // - 2025_01_27_160000_create_brands_content_board_block_texts_table.php
        throw new \RuntimeException(
            'Diese Migration ist irreversibel. Die Content Board Tabellen wurden permanent entfernt (Ticket #441). '
            . 'Zum Wiederherstellen die originalen Create-Migrations erneut ausführen.'
        );
    }
};
