<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsCiBoard;
use Livewire\Attributes\On;

class CiBoard extends Component
{
    public BrandsCiBoard $ciBoard;

    public function mount(BrandsCiBoard $brandsCiBoard)
    {
        $this->ciBoard = $brandsCiBoard;
        
        // Berechtigung prüfen
        $this->authorize('view', $this->ciBoard);
    }

    #[On('updateCiBoard')] 
    public function updateCiBoard()
    {
        $this->ciBoard->refresh();
    }

    public function updated($propertyName)
    {
        // Auto-Save bei Änderungen
        if (str_starts_with($propertyName, 'ciBoard.')) {
            $this->ciBoard->save();
        }
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.ci-board', [
            'user' => $user,
            'board' => $this->ciBoard, // Für Kompatibilität in der View
        ])->layout('platform::layouts.app');
    }
}
