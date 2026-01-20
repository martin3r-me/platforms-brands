<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsMultiContentBoard;
use Platform\Brands\Models\BrandsMultiContentBoardSlot;
use Platform\Brands\Models\BrandsContentBoard;
use Livewire\Attributes\On;

class MultiContentBoard extends Component
{
    public BrandsMultiContentBoard $multiContentBoard;

    public function mount(BrandsMultiContentBoard $brandsMultiContentBoard)
    {
        // Model neu laden, um sicherzustellen, dass alle Daten vorhanden sind
        $this->multiContentBoard = $brandsMultiContentBoard->fresh()->load('slots.contentBoards');
        
        // Berechtigung prüfen
        $this->authorize('view', $this->multiContentBoard);
    }

    #[On('updateMultiContentBoard')] 
    public function updateMultiContentBoard()
    {
        $this->multiContentBoard->refresh();
        $this->multiContentBoard->load('slots.contentBoards');
    }

    public function rules(): array
    {
        return [
            'multiContentBoard.name' => 'required|string|max:255',
            'multiContentBoard.description' => 'nullable|string',
        ];
    }

    public function createSlot()
    {
        $this->authorize('update', $this->multiContentBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $maxOrder = $this->multiContentBoard->slots()->max('order') ?? 0;

        BrandsMultiContentBoardSlot::create([
            'name' => 'Neuer Slot',
            'order' => $maxOrder + 1,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'multi_content_board_id' => $this->multiContentBoard->id,
        ]);

        $this->multiContentBoard->refresh();
        $this->multiContentBoard->load('slots.contentBoards');
    }

    public function createContentBoard($slotId = null)
    {
        $this->authorize('update', $this->multiContentBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $maxOrder = BrandsContentBoard::where('multi_content_board_slot_id', $slotId)
            ->max('order') ?? 0;

        BrandsContentBoard::create([
            'name' => 'Neues Content Board',
            'description' => null,
            'order' => $maxOrder + 1,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'brand_id' => $this->multiContentBoard->brand_id,
            'multi_content_board_slot_id' => $slotId,
        ]);

        $this->multiContentBoard->refresh();
        $this->multiContentBoard->load('slots.contentBoards');
    }

    /**
     * Aktualisiert Reihenfolge und Slot-Zugehörigkeit der Content Boards nach Drag&Drop.
     */
    public function updateContentBoardOrder($groups)
    {
        $this->authorize('update', $this->multiContentBoard);
        
        foreach ($groups as $group) {
            $slotId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $contentBoard = BrandsContentBoard::find($item['value']);

                if (!$contentBoard) {
                    continue;
                }

                $contentBoard->order = $item['order'];
                $contentBoard->multi_content_board_slot_id = $slotId;
                $contentBoard->save();
            }
        }

        $this->multiContentBoard->refresh();
        $this->multiContentBoard->load('slots.contentBoards');
    }

    /**
     * Aktualisiert Reihenfolge der Slots nach Drag&Drop.
     */
    public function updateSlotOrder($groups)
    {
        $this->authorize('update', $this->multiContentBoard);
        
        foreach ($groups as $group) {
            $slot = BrandsMultiContentBoardSlot::find($group['value']);
            if ($slot) {
                $slot->order = $group['order'];
                $slot->save();
            }
        }

        $this->multiContentBoard->refresh();
        $this->multiContentBoard->load('slots.contentBoards');
    }

    public function render()
    {
        $user = Auth::user();

        // Slots mit Content Boards und Sections laden
        $slots = $this->multiContentBoard->slots()->with(['contentBoards.sections', 'contentBoards.multiContentBoardSlot'])->orderBy('order')->get();

        return view('brands::livewire.multi-content-board', [
            'user' => $user,
            'slots' => $slots,
        ])->layout('platform::layouts.app');
    }
}
