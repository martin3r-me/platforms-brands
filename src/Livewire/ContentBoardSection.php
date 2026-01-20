<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsContentBoardSection;
use Livewire\Attributes\On;

class ContentBoardSection extends Component
{
    public BrandsContentBoardSection $section;

    public function mount(BrandsContentBoardSection $brandsContentBoardSection)
    {
        // Model neu laden, um sicherzustellen, dass alle Daten vorhanden sind
        $this->section = $brandsContentBoardSection->fresh()->load(['contentBoard', 'rows.blocks']);
        
        // Berechtigung prüfen
        $this->authorize('view', $this->section->contentBoard);
    }

    #[On('updateSection')] 
    public function updateSection()
    {
        $this->section->refresh();
        $this->section->load('rows.blocks');
    }

    public function createRow()
    {
        $this->authorize('update', $this->section->contentBoard);
        
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
            'section_id' => $this->section->id,
        ]);

        $this->section->refresh();
        $this->section->load('rows.blocks');
    }

    public function createBlock($rowId)
    {
        $this->authorize('update', $this->section->contentBoard);
        
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

        $this->section->refresh();
        $this->section->load('rows.blocks');
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.content-board-section', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
