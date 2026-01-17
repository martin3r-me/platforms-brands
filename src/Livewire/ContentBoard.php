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
        $this->contentBoard = $brandsContentBoard->fresh();
        
        // Berechtigung prüfen
        $this->authorize('view', $this->contentBoard);
    }

    #[On('updateContentBoard')] 
    public function updateContentBoard()
    {
        $this->contentBoard->refresh();
    }

    public function rules(): array
    {
        return [
            'contentBoard.name' => 'required|string|max:255',
            'contentBoard.description' => 'nullable|string',
        ];
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.content-board', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
