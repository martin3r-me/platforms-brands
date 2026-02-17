<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsPersonaBoard;
use Platform\Brands\Models\BrandsPersona;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Livewire\Attributes\On;

class PersonaBoard extends Component
{
    public BrandsPersonaBoard $personaBoard;

    public function mount(BrandsPersonaBoard $brandsPersonaBoard)
    {
        $this->personaBoard = $brandsPersonaBoard->fresh()->load(['personas', 'personas.toneOfVoiceBoard']);
        $this->authorize('view', $this->personaBoard);
    }

    #[On('updatePersonaBoard')]
    public function updatePersonaBoard()
    {
        $this->personaBoard->refresh();
        $this->personaBoard->load(['personas', 'personas.toneOfVoiceBoard']);
    }

    public function createPersona()
    {
        $this->authorize('update', $this->personaBoard);

        BrandsPersona::create([
            'persona_board_id' => $this->personaBoard->id,
            'name' => 'Neue Persona',
            'bio' => '',
        ]);

        $this->personaBoard->refresh();
        $this->personaBoard->load(['personas', 'personas.toneOfVoiceBoard']);
    }

    public function deletePersona($personaId)
    {
        $this->authorize('update', $this->personaBoard);

        $persona = BrandsPersona::findOrFail($personaId);
        $persona->delete();

        $this->personaBoard->refresh();
        $this->personaBoard->load(['personas', 'personas.toneOfVoiceBoard']);
    }

    public function updatePersonaOrder($groups)
    {
        $this->authorize('update', $this->personaBoard);

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $persona = BrandsPersona::find($item['value']);
                if ($persona) {
                    $persona->order = $item['order'];
                    $persona->save();
                }
            }
        }

        $this->personaBoard->refresh();
        $this->personaBoard->load(['personas', 'personas.toneOfVoiceBoard']);
    }

    public function render()
    {
        $user = Auth::user();
        $personas = $this->personaBoard->personas()->orderBy('order')->with('toneOfVoiceBoard')->get();

        // Tone of Voice Boards der gleichen Marke für Verknüpfung laden
        $toneOfVoiceBoards = BrandsToneOfVoiceBoard::where('brand_id', $this->personaBoard->brand_id)
            ->orderBy('name')
            ->get();

        return view('brands::livewire.persona-board', [
            'user' => $user,
            'personas' => $personas,
            'toneOfVoiceBoards' => $toneOfVoiceBoards,
        ])->layout('platform::layouts.app');
    }
}
