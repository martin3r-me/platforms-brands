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
        $this->contentBoard = $brandsContentBoard->fresh()->load('blocks.content', 'multiContentBoardSlot.multiContentBoard');

        $this->authorize('view', $this->contentBoard);
    }

    #[On('updateContentBoard')]
    public function updateContentBoard()
    {
        $this->contentBoard->refresh();
        $this->contentBoard->load('blocks.content', 'multiContentBoardSlot.multiContentBoard');
    }

    public function rules(): array
    {
        return [
            'contentBoard.name' => 'required|string|max:255',
            'contentBoard.description' => 'nullable|string',
        ];
    }

    public function createBlock()
    {
        $this->authorize('update', $this->contentBoard);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        \Platform\Brands\Models\BrandsContentBoardBlock::create([
            'name' => 'Neuer Block',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'content_board_id' => $this->contentBoard->id,
        ]);

        $this->contentBoard->refresh();
        $this->contentBoard->load('blocks.content');
    }

    public function deleteBlock($blockId)
    {
        $this->authorize('update', $this->contentBoard);

        $block = \Platform\Brands\Models\BrandsContentBoardBlock::findOrFail($blockId);
        $block->delete();

        $this->contentBoard->refresh();
        $this->contentBoard->load('blocks.content');
    }

    public function updateBlockName($blockId, $newName)
    {
        $this->authorize('update', $this->contentBoard);

        $block = \Platform\Brands\Models\BrandsContentBoardBlock::findOrFail($blockId);
        $block->name = trim($newName);
        $block->save();

        $this->contentBoard->refresh();
        $this->contentBoard->load('blocks.content');
    }

    /**
     * Aktualisiert die Reihenfolge der Blocks nach Drag&Drop
     */
    public function updateBlockOrder($items)
    {
        $this->authorize('update', $this->contentBoard);

        foreach ($items as $item) {
            $block = \Platform\Brands\Models\BrandsContentBoardBlock::find($item['value']);

            if (!$block) {
                continue;
            }

            $block->order = $item['order'];
            $block->save();
        }

        $this->contentBoard->refresh();
        $this->contentBoard->load('blocks.content');
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.content-board', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
