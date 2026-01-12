<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsCiBoard;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class CiBoard extends Component
{
    public BrandsCiBoard $ciBoard;

    public function mount(BrandsCiBoard $brandsCiBoard)
    {
        // Model neu laden, um sicherzustellen, dass alle Daten vorhanden sind
        $this->ciBoard = $brandsCiBoard->fresh();
        
        // Berechtigung prüfen
        $this->authorize('view', $this->ciBoard);
    }

    #[On('updateCiBoard')] 
    public function updateCiBoard()
    {
        $this->ciBoard->refresh();
    }

    public function rules(): array
    {
        return [
            'ciBoard.name' => 'required|string|max:255',
            'ciBoard.description' => 'nullable|string',
            'ciBoard.primary_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'ciBoard.secondary_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'ciBoard.accent_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'ciBoard.slogan' => 'nullable|string|max:500',
            'ciBoard.font_family' => 'nullable|string|max:255',
            'ciBoard.tagline' => 'nullable|string|max:500',
        ];
    }

    #[Computed]
    public function isDirty(): bool
    {
        if (!$this->ciBoard) {
            return false;
        }
        
        // Prüfe ob das Model Änderungen hat
        return count($this->ciBoard->getDirty()) > 0;
    }

    public function save()
    {
        $this->validate();
        
        // Policy prüfen
        $this->authorize('update', $this->ciBoard);
        
        // Speichern
        $this->ciBoard->save();
        $this->ciBoard->refresh();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updateCiBoard');
        
        $this->dispatch('notifications:store', [
            'title' => 'CI Board gespeichert',
            'message' => 'Die Änderungen wurden erfolgreich gespeichert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->ciBoard),
            'noticable_id'   => $this->ciBoard->getKey(),
        ]);
    }

    public function updated($propertyName)
    {
        // Validierung bei Änderungen
        if (str_starts_with($propertyName, 'ciBoard.')) {
            $field = str_replace('ciBoard.', '', $propertyName);
            $this->validateOnly("ciBoard.$field");
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
