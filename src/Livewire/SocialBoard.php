<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsSocialBoard;
use Platform\Brands\Models\BrandsSocialBoardSlot;
use Platform\Brands\Models\BrandsSocialCard;
use Livewire\Attributes\On;

class SocialBoard extends Component
{
    public BrandsSocialBoard $socialBoard;

    public function mount(BrandsSocialBoard $brandsSocialBoard)
    {
        // Model neu laden, um sicherzustellen, dass alle Daten vorhanden sind
        $this->socialBoard = $brandsSocialBoard->fresh()->load('slots.cards');
        
        // Berechtigung prüfen
        $this->authorize('view', $this->socialBoard);
    }

    #[On('updateSocialBoard')] 
    public function updateSocialBoard()
    {
        $this->socialBoard->refresh();
        $this->socialBoard->load('slots.cards');
    }

    public function rules(): array
    {
        return [
            'socialBoard.name' => 'required|string|max:255',
            'socialBoard.description' => 'nullable|string',
        ];
    }

    public function createSlot()
    {
        $this->authorize('update', $this->socialBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $maxOrder = $this->socialBoard->slots()->max('order') ?? 0;

        BrandsSocialBoardSlot::create([
            'name' => 'Neuer Slot',
            'order' => $maxOrder + 1,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'social_board_id' => $this->socialBoard->id,
        ]);

        $this->socialBoard->refresh();
        $this->socialBoard->load('slots.cards');
    }

    public function createCard($slotId = null)
    {
        $this->authorize('update', $this->socialBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        $maxOrder = BrandsSocialCard::where('social_board_slot_id', $slotId)
            ->max('order') ?? 0;

        BrandsSocialCard::create([
            'title' => 'Neue Card',
            'description' => null,
            'order' => $maxOrder + 1,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'social_board_id' => $this->socialBoard->id,
            'social_board_slot_id' => $slotId,
        ]);

        $this->socialBoard->refresh();
        $this->socialBoard->load('slots.cards');
    }

    /**
     * Aktualisiert Reihenfolge und Slot-Zugehörigkeit der Cards nach Drag&Drop.
     */
    public function updateCardOrder($groups)
    {
        $this->authorize('update', $this->socialBoard);
        
        foreach ($groups as $group) {
            $slotId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $card = BrandsSocialCard::find($item['value']);

                if (!$card) {
                    continue;
                }

                $card->order = $item['order'];
                $card->social_board_slot_id = $slotId;
                $card->save();
            }
        }

        $this->socialBoard->refresh();
        $this->socialBoard->load('slots.cards');
    }

    /**
     * Aktualisiert Reihenfolge der Slots nach Drag&Drop.
     */
    public function updateSlotOrder($groups)
    {
        $this->authorize('update', $this->socialBoard);
        
        foreach ($groups as $group) {
            $slot = BrandsSocialBoardSlot::find($group['value']);
            if ($slot) {
                $slot->order = $group['order'];
                $slot->save();
            }
        }

        $this->socialBoard->refresh();
        $this->socialBoard->load('slots.cards');
    }

    public function render()
    {
        $user = Auth::user();

        // Slots mit Cards laden
        $slots = $this->socialBoard->slots()->with('cards')->orderBy('order')->get();

        return view('brands::livewire.social-board', [
            'user' => $user,
            'slots' => $slots,
        ])->layout('platform::layouts.app');
    }
}
