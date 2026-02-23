<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsIntakeSession;
use Illuminate\Support\Facades\Gate;

class IntakeSessionView extends Component
{
    public BrandsIntakeSession $session;

    public function mount(BrandsIntakeSession $brandsIntakeSession)
    {
        // Auth check via board
        $board = $brandsIntakeSession->intakeBoard;
        if (!$board || $board->team_id !== auth()->user()->currentTeam?->id) {
            abort(403);
        }
        $this->session = $brandsIntakeSession;
    }

    public function render()
    {
        $this->session->load(['intakeBoard.boardBlocks.blockDefinition']);

        $blocks = $this->session->intakeBoard->boardBlocks
            ->sortBy('sort_order')
            ->values();

        $answers = $this->session->answers ?? [];

        return view('brands::livewire.intake-session-view', [
            'blocks' => $blocks,
            'answers' => $answers,
        ]);
    }
}
