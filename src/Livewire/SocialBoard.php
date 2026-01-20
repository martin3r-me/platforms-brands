<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsSocialBoard;
use Livewire\Attributes\On;

class SocialBoard extends Component
{
    public BrandsSocialBoard $socialBoard;

    public function mount(BrandsSocialBoard $brandsSocialBoard)
    {
        // Model neu laden, um sicherzustellen, dass alle Daten vorhanden sind
        $this->socialBoard = $brandsSocialBoard->fresh();
        
        // Berechtigung prüfen
        $this->authorize('view', $this->socialBoard);
    }

    #[On('updateSocialBoard')] 
    public function updateSocialBoard()
    {
        $this->socialBoard->refresh();
    }

    public function rules(): array
    {
        return [
            'socialBoard.name' => 'required|string|max:255',
            'socialBoard.description' => 'nullable|string',
        ];
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.social-board', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
