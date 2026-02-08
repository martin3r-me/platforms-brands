<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsKanbanBoard;
use Platform\Brands\Models\BrandsKanbanBoardSlot;
use Platform\Brands\Models\BrandsKanbanCard;
use Livewire\Attributes\On;

class KanbanBoard extends Component
{
    public BrandsKanbanBoard $kanbanBoard;

    public function mount(BrandsKanbanBoard $brandsKanbanBoard)
    {
        // Model neu laden, um sicherzustellen, dass alle Daten vorhanden sind
        $this->kanbanBoard = $brandsKanbanBoard->fresh()->load('slots.cards');

        // Berechtigung prüfen
        $this->authorize('view', $this->kanbanBoard);
    }

    #[On('updateKanbanBoard')]
    public function updateKanbanBoard()
    {
        $this->kanbanBoard->refresh();
        $this->kanbanBoard->load('slots.cards');
    }

    public function rules(): array
    {
        return [
            'kanbanBoard.name' => 'required|string|max:255',
            'kanbanBoard.description' => 'nullable|string',
        ];
    }

    public function createSlot()
    {
        $this->authorize('update', $this->kanbanBoard);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $maxOrder = $this->kanbanBoard->slots()->max('order') ?? 0;

        BrandsKanbanBoardSlot::create([
            'name' => 'Neuer Slot',
            'order' => $maxOrder + 1,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'kanban_board_id' => $this->kanbanBoard->id,
        ]);

        $this->kanbanBoard->refresh();
        $this->kanbanBoard->load('slots.cards');
    }

    public function createCard($slotId = null)
    {
        $this->authorize('update', $this->kanbanBoard);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $maxOrder = BrandsKanbanCard::where('kanban_board_slot_id', $slotId)
            ->max('order') ?? 0;

        BrandsKanbanCard::create([
            'title' => 'Neue Card',
            'description' => null,
            'order' => $maxOrder + 1,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'kanban_board_id' => $this->kanbanBoard->id,
            'kanban_board_slot_id' => $slotId,
        ]);

        $this->kanbanBoard->refresh();
        $this->kanbanBoard->load('slots.cards');
    }

    /**
     * Aktualisiert Reihenfolge und Slot-Zugehörigkeit der Cards nach Drag&Drop.
     */
    public function updateCardOrder($groups)
    {
        $this->authorize('update', $this->kanbanBoard);

        foreach ($groups as $group) {
            $slotId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $card = BrandsKanbanCard::find($item['value']);

                if (!$card) {
                    continue;
                }

                $card->order = $item['order'];
                $card->kanban_board_slot_id = $slotId;
                $card->save();
            }
        }

        $this->kanbanBoard->refresh();
        $this->kanbanBoard->load('slots.cards');
    }

    /**
     * Aktualisiert Reihenfolge der Slots nach Drag&Drop.
     */
    public function updateSlotOrder($groups)
    {
        $this->authorize('update', $this->kanbanBoard);

        foreach ($groups as $group) {
            $slot = BrandsKanbanBoardSlot::find($group['value']);
            if ($slot) {
                $slot->order = $group['order'];
                $slot->save();
            }
        }

        $this->kanbanBoard->refresh();
        $this->kanbanBoard->load('slots.cards');
    }

    public function render()
    {
        $user = Auth::user();

        // Slots mit Cards und Slot-Relation laden
        $slots = $this->kanbanBoard->slots()->with(['cards.slot'])->orderBy('order')->get();

        return view('brands::livewire.kanban-board', [
            'user' => $user,
            'slots' => $slots,
        ])->layout('platform::layouts.app');
    }
}
