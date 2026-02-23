<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Brands\Models\BrandsIntakeBoard;
use Platform\Brands\Models\BrandsIntakeBoardBlock;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;
use Illuminate\Support\Facades\Gate;

class IntakeBoard extends Component
{
    public BrandsIntakeBoard $intakeBoard;
    public bool $showSettingsModal = false;
    public bool $showAddBlockModal = false;
    public ?int $selectedDefinitionId = null;
    public bool $isRequired = false;

    public function mount(BrandsIntakeBoard $brandsIntakeBoard)
    {
        Gate::authorize('view', $brandsIntakeBoard);
        $this->intakeBoard = $brandsIntakeBoard;
    }

    public function publish()
    {
        Gate::authorize('update', $this->intakeBoard);
        $this->intakeBoard->publish();
    }

    public function close()
    {
        Gate::authorize('update', $this->intakeBoard);
        $this->intakeBoard->close();
    }

    public function unpublish()
    {
        Gate::authorize('update', $this->intakeBoard);
        $this->intakeBoard->unpublish();
    }

    public function addBlock()
    {
        Gate::authorize('update', $this->intakeBoard);

        if (!$this->selectedDefinitionId) return;

        $maxOrder = $this->intakeBoard->boardBlocks()->max('sort_order') ?? -1;

        BrandsIntakeBoardBlock::create([
            'intake_board_id' => $this->intakeBoard->id,
            'block_definition_id' => $this->selectedDefinitionId,
            'sort_order' => $maxOrder + 1,
            'is_required' => $this->isRequired,
            'user_id' => auth()->id(),
            'team_id' => $this->intakeBoard->team_id,
        ]);

        $this->selectedDefinitionId = null;
        $this->isRequired = false;
        $this->showAddBlockModal = false;
    }

    public function removeBlock(int $blockId)
    {
        Gate::authorize('update', $this->intakeBoard);
        BrandsIntakeBoardBlock::where('id', $blockId)
            ->where('intake_board_id', $this->intakeBoard->id)
            ->delete();
    }

    public function moveBlock(int $blockId, string $direction)
    {
        Gate::authorize('update', $this->intakeBoard);
        $block = BrandsIntakeBoardBlock::find($blockId);
        if (!$block || $block->intake_board_id !== $this->intakeBoard->id) return;

        $blocks = $this->intakeBoard->boardBlocks()->orderBy('sort_order')->get();
        $index = $blocks->search(fn($b) => $b->id === $blockId);

        if ($direction === 'up' && $index > 0) {
            $other = $blocks[$index - 1];
            $tmpOrder = $block->sort_order;
            $block->update(['sort_order' => $other->sort_order]);
            $other->update(['sort_order' => $tmpOrder]);
        } elseif ($direction === 'down' && $index < $blocks->count() - 1) {
            $other = $blocks[$index + 1];
            $tmpOrder = $block->sort_order;
            $block->update(['sort_order' => $other->sort_order]);
            $other->update(['sort_order' => $tmpOrder]);
        }
    }

    #[On('settings-updated')]
    public function refreshBoard()
    {
        $this->intakeBoard->refresh();
    }

    public function render()
    {
        $blocks = $this->intakeBoard->boardBlocks()
            ->with('blockDefinition')
            ->orderBy('sort_order')
            ->get();

        $sessions = $this->intakeBoard->sessions()
            ->latest()
            ->limit(20)
            ->get();

        $availableDefinitions = BrandsIntakeBlockDefinition::where('team_id', $this->intakeBoard->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('brands::livewire.intake-board', [
            'blocks' => $blocks,
            'sessions' => $sessions,
            'availableDefinitions' => $availableDefinitions,
        ]);
    }
}
