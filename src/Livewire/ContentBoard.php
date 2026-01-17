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
        $this->contentBoard = $brandsContentBoard->fresh()->load('sections.rows.blocks');
        
        // Berechtigung prüfen
        $this->authorize('view', $this->contentBoard);
    }

    #[On('updateContentBoard')] 
    public function updateContentBoard()
    {
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks');
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
        $this->contentBoard->load('sections.rows.blocks');
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
        $this->contentBoard->load('sections.rows.blocks');
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
        
        // Prüfe, ob bereits 12 Blöcke in dieser Row existieren
        $existingBlocks = $row->blocks()->count();
        if ($existingBlocks >= 12) {
            session()->flash('error', 'Eine Row kann maximal 12 Blöcke enthalten.');
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
        $this->contentBoard->load('sections.rows.blocks');
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
        $this->contentBoard->load('sections.rows.blocks');
    }

    public function deleteBlock($blockId)
    {
        $this->authorize('update', $this->contentBoard);
        
        $block = \Platform\Brands\Models\BrandsContentBoardBlock::findOrFail($blockId);
        $block->delete();
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks');
    }

    public function deleteRow($rowId)
    {
        $this->authorize('update', $this->contentBoard);
        
        $row = \Platform\Brands\Models\BrandsContentBoardRow::findOrFail($rowId);
        $row->delete();
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks');
    }

    public function deleteSection($sectionId)
    {
        $this->authorize('update', $this->contentBoard);
        
        $section = \Platform\Brands\Models\BrandsContentBoardSection::findOrFail($sectionId);
        $section->delete();
        
        $this->contentBoard->refresh();
        $this->contentBoard->load('sections.rows.blocks');
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.content-board', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
