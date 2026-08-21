<?php

namespace Platform\Brands\Concerns;

use Illuminate\Database\Eloquent\Model;
use Platform\Brands\Models\BrandsBrand;

/**
 * Verdrahtet eine Board-Livewire-Komponente mit dem Core-Terminal:
 * Dateien + Aktivitäten werden im Core gebündelt, die Komponente setzt nur den
 * Kontext (analog Planner-Task).
 *
 *   context = Board (context_type + context_id)
 *   linked  = Parent-Brand (damit die Zuordnung nach oben eindeutig ist)
 *
 * 13 Boards nutzen das automatische rendered() dieses Traits. Boards mit eigenem
 * rendered() (z. B. KanbanBoard) rufen dispatchBoardContext() dort selbst auf.
 */
trait DispatchesBoardContext
{
    public function rendered(): void
    {
        $board = $this->resolveContextBoard();
        if ($board) {
            $this->dispatchBoardContext($board);
        }
    }

    /**
     * Findet die Board-Model-Property der Komponente über die board_types-Registry.
     */
    protected function resolveContextBoard(): ?Model
    {
        $models = collect(config('brands.board_types', []))->pluck('model')->filter()->all();

        foreach (get_object_vars($this) as $value) {
            if ($value instanceof Model && in_array(get_class($value), $models, true)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Setzt den Terminal-Kontext auf das Board und schaltet Dateien/Aktivitäten frei.
     */
    protected function dispatchBoardContext(Model $board): void
    {
        $class = get_class($board);
        $def = collect(config('brands.board_types', []))->firstWhere('model', $class);
        $url = ($def['route'] ?? null) ? route($def['route'], $board) : null;
        $brandId = $board->brand_id ?? null;

        // 1) Kontext setzen (Board als primärer Kontext)
        $this->dispatch('comms', [
            'model' => $class,
            'modelId' => $board->id,
            'subject' => method_exists($board, 'getDisplayName') ? $board->getDisplayName() : ($board->name ?? null),
            'description' => $board->description ?? '',
            'url' => $url,
            'source' => 'brands.board.view',
            'recipients' => [],
            'capabilities' => [
                'manage_channels' => false,
                'threads' => true,
            ],
            'meta' => [],
        ]);

        // 2) Organization-Kontext mit Parent-Brand als linked_context
        $this->dispatch('organization', [
            'context_type' => $class,
            'context_id' => $board->id,
            'linked_contexts' => $brandId ? [['type' => BrandsBrand::class, 'id' => $brandId]] : [],
            'allow_time_entry' => true,
            'allow_entities' => false,
            'allow_dimensions' => false,
        ]);

        // 3) Dateien- und Aktivitäten-Tab im Core-Terminal freischalten
        $this->dispatch('terminal:app:files');
        $this->dispatch('terminal:app:activity');
    }
}
