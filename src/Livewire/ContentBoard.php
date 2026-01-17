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

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.content-board', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
