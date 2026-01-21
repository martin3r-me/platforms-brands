<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsContentBoard;
use Livewire\Attributes\On;

class ContentBoard extends Component
{
    public BrandsContentBoard $contentBoard;

    public function mount(BrandsContentBoard $brandsContentBoard)
    {
        // Model neu laden, um sicherzustellen, dass alle Daten vorhanden sind
        $this->contentBoard = $brandsContentBoard->fresh()->load('sections.rows.blocks.content', 'multiContentBoardSlot.multiContentBoard');
        
        // Berechtigung prüfen
        $this->authorize('view', $this->contentBoard);
    }

    #[On('updateContentBoard')] 
    public function updateContentBoard()
    {
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content', 'multiContentBoardSlot.multiContentBoard');
    }

    public function rules(): array
    {
        return [
            'contentBoard.name' => 'required|string|max:255',
            'contentBoard.description' => 'nullable|string',
        ];
    }

    public function createSection()
    {
        $this->authorize('update', $this->contentBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $section = \Platform\Brands\Models\BrandsContentBoardSection::create([
            'name' => 'Neue Section',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'content_board_id' => $this->contentBoard->id,
        ]);

        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    public function createRow($sectionId)
    {
        $this->authorize('update', $this->contentBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $row = \Platform\Brands\Models\BrandsContentBoardRow::create([
            'name' => 'Neue Row',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'section_id' => $sectionId,
        ]);

        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    public function createBlock($rowId)
    {
        $this->authorize('update', $this->contentBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $row = \Platform\Brands\Models\BrandsContentBoardRow::findOrFail($rowId);
        
        // Prüfe, ob die Summe der Spans bereits 12 erreicht hat
        $currentSpanSum = $row->blocks()->sum('span');
        if ($currentSpanSum >= 12) {
            session()->flash('error', 'Die Summe aller Spans in einer Row darf maximal 12 betragen. Aktuell: ' . $currentSpanSum . '/12.');
            return;
        }

        $block = \Platform\Brands\Models\BrandsContentBoardBlock::create([
            'name' => 'Neuer Block',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'row_id' => $rowId,
            'span' => 1,
        ]);

        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    public function updateBlockSpan($blockId, $newSpan)
    {
        $this->authorize('update', $this->contentBoard);
        
        $block = \Platform\Brands\Models\BrandsContentBoardBlock::findOrFail($blockId);
        $row = $block->row;
        
        // Validierung: span muss zwischen 1 und 12 sein
        $newSpan = max(1, min(12, (int)$newSpan));
        
        // Berechne die aktuelle Summe aller Spans in dieser Row
        $currentSum = $row->blocks()->sum('span');
        $currentBlockSpan = $block->span;
        $newSum = $currentSum - $currentBlockSpan + $newSpan;
        
        // Prüfe, ob die neue Summe 12 nicht überschreitet
        if ($newSum > 12) {
            session()->flash('error', "Die Summe aller Spans in einer Row darf nicht mehr als 12 betragen. Aktuell: {$currentSum}, mit neuem Wert: {$newSum}.");
            return;
        }
        
        $block->span = $newSpan;
        $block->save();
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    public function deleteBlock($blockId)
    {
        $this->authorize('update', $this->contentBoard);
        
        $block = \Platform\Brands\Models\BrandsContentBoardBlock::findOrFail($blockId);
        $block->delete();
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    public function deleteRow($rowId)
    {
        $this->authorize('update', $this->contentBoard);
        
        $row = \Platform\Brands\Models\BrandsContentBoardRow::findOrFail($rowId);
        $row->delete();
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    public function deleteSection($sectionId)
    {
        $this->authorize('update', $this->contentBoard);
        
        $section = \Platform\Brands\Models\BrandsContentBoardSection::findOrFail($sectionId);
        $section->delete();
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    public function updateSectionName($sectionId, $newName)
    {
        $this->authorize('update', $this->contentBoard);
        
        $section = \Platform\Brands\Models\BrandsContentBoardSection::findOrFail($sectionId);
        $section->name = trim($newName);
        $section->save();
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    public function updateRowName($rowId, $newName)
    {
        $this->authorize('update', $this->contentBoard);
        
        $row = \Platform\Brands\Models\BrandsContentBoardRow::findOrFail($rowId);
        $row->name = trim($newName);
        $row->save();
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    public function updateBlockName($blockId, $newName)
    {
        $this->authorize('update', $this->contentBoard);
        
        $block = \Platform\Brands\Models\BrandsContentBoardBlock::findOrFail($blockId);
        $block->name = trim($newName);
        $block->save();
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    /**
     * Aktualisiert die Reihenfolge der Sections nach Drag&Drop
     */
    public function updateSectionOrder($sections)
    {
        $this->authorize('update', $this->contentBoard);
        
        foreach ($sections as $section) {
            $sectionDb = \Platform\Brands\Models\BrandsContentBoardSection::find($section['value']);
            if ($sectionDb) {
                $sectionDb->order = $section['order'];
                $sectionDb->save();
            }
        }
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    /**
     * Aktualisiert die Reihenfolge der Rows innerhalb einer Section nach Drag&Drop
     */
    public function updateRowOrder($groups)
    {
        $this->authorize('update', $this->contentBoard);
        
        foreach ($groups as $group) {
            $sectionId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $row = \Platform\Brands\Models\BrandsContentBoardRow::find($item['value']);

                if (!$row) {
                    continue;
                }

                $row->order = $item['order'];
                $row->section_id = $sectionId;
                $row->save();
            }
        }
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    /**
     * Aktualisiert die Reihenfolge der Blocks innerhalb einer Row nach Drag&Drop
     */
    public function updateBlockOrder($groups)
    {
        $this->authorize('update', $this->contentBoard);
        
        foreach ($groups as $group) {
            $rowId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $block = \Platform\Brands\Models\BrandsContentBoardBlock::find($item['value']);

                if (!$block) {
                    continue;
                }

                $block->order = $item['order'];
                $block->row_id = $rowId;
                $block->save();
            }
        }
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks.content');
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.content-board', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
