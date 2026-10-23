<?php

namespace Platform\Brands\Concerns;

use Illuminate\Database\Eloquent\Model;
use Platform\Core\Livewire\Concerns\HasPageContext;

/**
 * Seitenkontext für Board-Livewire-Komponenten (Terminal + Kontext-Panel) über
 * den Core-Trait HasPageContext: Kontext = das Board der Komponente, Apps werden
 * aus dem Board-Modell abgeleitet.
 *
 * 13 Boards nutzen das automatische rendered() von HasPageContext. Boards mit eigenem
 * rendered() rufen dort dispatchPageContext() (oder weiterhin dispatchBoardContext()) auf.
 */
trait DispatchesBoardContext
{
    use HasPageContext;

    protected function pageContext(): ?Model
    {
        return $this->resolveContextBoard();
    }

    protected function pageContextOptions(): array
    {
        $board = $this->resolveContextBoard();
        $def = $board ? collect(config('brands.board_types', []))->firstWhere('model', get_class($board)) : null;

        return [
            'url' => ($board && ($def['route'] ?? null)) ? route($def['route'], $board) : null,
            'source' => 'brands.board.view',
            // Board-Modelle haben weder LogsActivity noch HasTimeEntries — Tabs wie bisher erzwingen
            'apps' => ['activity' => true, 'time' => true],
        ];
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
     * Kompatibilität für Boards mit eigenem rendered(): delegiert an dispatchPageContext().
     * Das Board wird über resolveContextBoard() bestimmt.
     */
    protected function dispatchBoardContext(?Model $board = null): void
    {
        $this->dispatchPageContext();
    }
}
