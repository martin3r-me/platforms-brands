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
        
        // Farben initialisieren, falls null (für Color-Input benötigt)
        if (empty($this->ciBoard->primary_color)) {
            $this->ciBoard->primary_color = '#000000';
        }
        if (empty($this->ciBoard->secondary_color)) {
            $this->ciBoard->secondary_color = '#000000';
        }
        if (empty($this->ciBoard->accent_color)) {
            $this->ciBoard->accent_color = '#000000';
        }
        
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
        // Farbwerte normalisieren vor dem Speichern
        foreach (['primary_color', 'secondary_color', 'accent_color'] as $colorField) {
            $value = $this->ciBoard->$colorField;
            if (!empty($value)) {
                $value = ltrim($value, '#');
                if (strlen($value) === 3) {
                    $value = $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
                }
                $this->ciBoard->$colorField = '#' . $value;
            } else {
                // Wenn leer, setze auf null (nicht auf #000000)
                $this->ciBoard->$colorField = null;
            }
        }
        
        $this->validate();
        
        // Policy prüfen
        $this->authorize('update', $this->ciBoard);
        
        // Speichern
        $this->ciBoard->save();
        $this->ciBoard->refresh();
        
        // Nach dem Speichern wieder Standard-Werte für Color-Input setzen, falls null
        if (empty($this->ciBoard->primary_color)) {
            $this->ciBoard->primary_color = '#000000';
        }
        if (empty($this->ciBoard->secondary_color)) {
            $this->ciBoard->secondary_color = '#000000';
        }
        if (empty($this->ciBoard->accent_color)) {
            $this->ciBoard->accent_color = '#000000';
        }
        
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
            
            // Farbwerte normalisieren (entferne # falls vorhanden, dann wieder hinzufügen)
            if (in_array($field, ['primary_color', 'secondary_color', 'accent_color'])) {
                $value = $this->ciBoard->$field;
                if (!empty($value)) {
                    // Entferne # falls vorhanden
                    $value = ltrim($value, '#');
                    // Stelle sicher, dass es 6 Hex-Zeichen sind
                    if (strlen($value) === 3) {
                        // Expandiere #RGB zu #RRGGBB
                        $value = $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
                    }
                    // Füge # hinzu falls nicht vorhanden
                    if (!str_starts_with($value, '#')) {
                        $value = '#' . $value;
                    }
                    $this->ciBoard->$field = $value;
                } else {
                    // Wenn leer, setze auf Standard
                    $this->ciBoard->$field = '#000000';
                }
            }
            
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
