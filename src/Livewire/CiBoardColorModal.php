<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsCiBoard;
use Platform\Brands\Models\BrandsCiBoardColor;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class CiBoardColorModal extends Component
{
    public $modalShow = false;
    public $ciBoard;
    public $color;
    public $isEdit = false;

    #[On('open-modal-ci-board-color')] 
    public function openModalCiBoardColor($ciBoardId, $colorId = null)
    {
        $this->ciBoard = BrandsCiBoard::findOrFail($ciBoardId);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->ciBoard);
        
        if ($colorId) {
            // Bearbeitungsmodus
            $this->color = BrandsCiBoardColor::where('brand_ci_board_id', $this->ciBoard->id)
                ->findOrFail($colorId);
            $this->isEdit = true;
        } else {
            // Erstellungsmodus
            $this->color = new BrandsCiBoardColor();
            $this->color->brand_ci_board_id = $this->ciBoard->id;
            $this->color->color = '#000000'; // Standard für Color-Input
            $this->isEdit = false;
        }
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'color.title' => 'required|string|max:255',
            'color.color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'color.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        // Farbwert normalisieren
        if (!empty($this->color->color)) {
            $colorValue = ltrim($this->color->color, '#');
            if (strlen($colorValue) === 3) {
                $colorValue = $colorValue[0] . $colorValue[0] . $colorValue[1] . $colorValue[1] . $colorValue[2] . $colorValue[2];
            }
            $this->color->color = '#' . $colorValue;
        } else {
            $this->color->color = null;
        }
        
        $this->validate();
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->ciBoard);

        $this->color->save();
        
        $this->dispatch('updateCiBoard');
        $this->dispatch('updateSidebar');

        $this->dispatch('notifications:store', [
            'title' => $this->isEdit ? 'Farbe aktualisiert' : 'Farbe erstellt',
            'message' => $this->isEdit 
                ? 'Die Farbe wurde erfolgreich aktualisiert.' 
                : 'Die Farbe wurde erfolgreich erstellt.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->color),
            'noticable_id'   => $this->color->getKey(),
        ]);

        $this->reset('color', 'ciBoard', 'isEdit');
        $this->closeModal();
    }

    public function deleteColor()
    {
        if (!$this->isEdit || !$this->color->id) {
            return;
        }
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->ciBoard);
        
        $this->color->delete();
        
        $this->dispatch('updateCiBoard');
        $this->dispatch('updateSidebar');

        $this->dispatch('notifications:store', [
            'title' => 'Farbe gelöscht',
            'message' => 'Die Farbe wurde erfolgreich gelöscht.',
            'notice_type' => 'success',
            'noticable_type' => BrandsCiBoardColor::class,
            'noticable_id'   => $this->color->id,
        ]);
        
        $this->reset('color', 'ciBoard', 'isEdit');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
        $this->reset('color', 'ciBoard', 'isEdit');
    }

    public function render()
    {
        return view('brands::livewire.ci-board-color-modal')->layout('platform::layouts.app');
    }
}
