<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsPersonaBoard;
use Platform\Brands\Models\BrandsPersona;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Livewire\Attributes\On;

class PersonaModal extends Component
{
    public $modalShow = false;
    public $persona;
    public $personaBoardId;

    // Persona fields
    public $personaName = '';
    public $personaAge = null;
    public $personaGender = '';
    public $personaOccupation = '';
    public $personaLocation = '';
    public $personaEducation = '';
    public $personaIncomeRange = '';
    public $personaBio = '';
    public $personaPainPoints = [];
    public $personaGoals = [];
    public $personaQuotes = [];
    public $personaBehaviors = [];
    public $personaChannels = [];
    public $personaBrandsLiked = [];
    public $personaToneOfVoiceBoardId = null;

    #[On('open-modal-persona')]
    public function openModal($personaBoardId, $personaId = null)
    {
        $this->personaBoardId = $personaBoardId;
        $this->resetFields();

        if ($personaId) {
            $this->persona = BrandsPersona::findOrFail($personaId);
            $this->fillFromPersona();
        } else {
            $this->persona = null;
        }

        $this->modalShow = true;
    }

    protected function resetFields()
    {
        $this->personaName = '';
        $this->personaAge = null;
        $this->personaGender = '';
        $this->personaOccupation = '';
        $this->personaLocation = '';
        $this->personaEducation = '';
        $this->personaIncomeRange = '';
        $this->personaBio = '';
        $this->personaPainPoints = [];
        $this->personaGoals = [];
        $this->personaQuotes = [];
        $this->personaBehaviors = [];
        $this->personaChannels = [];
        $this->personaBrandsLiked = [];
        $this->personaToneOfVoiceBoardId = null;
    }

    protected function fillFromPersona()
    {
        $this->personaName = $this->persona->name;
        $this->personaAge = $this->persona->age;
        $this->personaGender = $this->persona->gender ?? '';
        $this->personaOccupation = $this->persona->occupation ?? '';
        $this->personaLocation = $this->persona->location ?? '';
        $this->personaEducation = $this->persona->education ?? '';
        $this->personaIncomeRange = $this->persona->income_range ?? '';
        $this->personaBio = $this->persona->bio ?? '';
        $this->personaPainPoints = $this->persona->pain_points ?? [];
        $this->personaGoals = $this->persona->goals ?? [];
        $this->personaQuotes = $this->persona->quotes ?? [];
        $this->personaBehaviors = $this->persona->behaviors ?? [];
        $this->personaChannels = $this->persona->channels ?? [];
        $this->personaBrandsLiked = $this->persona->brands_liked ?? [];
        $this->personaToneOfVoiceBoardId = $this->persona->tone_of_voice_board_id;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'personaName' => 'required|string|max:255',
        ];
    }

    // Dynamic list management methods
    public function addPainPoint()
    {
        $this->personaPainPoints[] = ['text' => ''];
    }

    public function removePainPoint($index)
    {
        unset($this->personaPainPoints[$index]);
        $this->personaPainPoints = array_values($this->personaPainPoints);
    }

    public function addGoal()
    {
        $this->personaGoals[] = ['text' => ''];
    }

    public function removeGoal($index)
    {
        unset($this->personaGoals[$index]);
        $this->personaGoals = array_values($this->personaGoals);
    }

    public function addQuote()
    {
        $this->personaQuotes[] = ['text' => ''];
    }

    public function removeQuote($index)
    {
        unset($this->personaQuotes[$index]);
        $this->personaQuotes = array_values($this->personaQuotes);
    }

    public function addBehavior()
    {
        $this->personaBehaviors[] = ['text' => ''];
    }

    public function removeBehavior($index)
    {
        unset($this->personaBehaviors[$index]);
        $this->personaBehaviors = array_values($this->personaBehaviors);
    }

    public function addChannel()
    {
        $this->personaChannels[] = ['text' => ''];
    }

    public function removeChannel($index)
    {
        unset($this->personaChannels[$index]);
        $this->personaChannels = array_values($this->personaChannels);
    }

    public function addBrandLiked()
    {
        $this->personaBrandsLiked[] = ['text' => ''];
    }

    public function removeBrandLiked($index)
    {
        unset($this->personaBrandsLiked[$index]);
        $this->personaBrandsLiked = array_values($this->personaBrandsLiked);
    }

    public function save()
    {
        $this->validate();

        $board = BrandsPersonaBoard::findOrFail($this->personaBoardId);
        $this->authorize('update', $board);

        // Filter empty entries from arrays
        $filterEmpty = fn($arr) => array_values(array_filter($arr, fn($item) => !empty($item['text'])));

        $data = [
            'name' => $this->personaName,
            'age' => $this->personaAge ?: null,
            'gender' => $this->personaGender ?: null,
            'occupation' => $this->personaOccupation ?: null,
            'location' => $this->personaLocation ?: null,
            'education' => $this->personaEducation ?: null,
            'income_range' => $this->personaIncomeRange ?: null,
            'bio' => $this->personaBio ?: null,
            'pain_points' => $filterEmpty($this->personaPainPoints) ?: null,
            'goals' => $filterEmpty($this->personaGoals) ?: null,
            'quotes' => $filterEmpty($this->personaQuotes) ?: null,
            'behaviors' => $filterEmpty($this->personaBehaviors) ?: null,
            'channels' => $filterEmpty($this->personaChannels) ?: null,
            'brands_liked' => $filterEmpty($this->personaBrandsLiked) ?: null,
            'tone_of_voice_board_id' => $this->personaToneOfVoiceBoardId ?: null,
        ];

        if ($this->persona) {
            $this->persona->update($data);
        } else {
            $data['persona_board_id'] = $this->personaBoardId;
            BrandsPersona::create($data);
        }

        $this->dispatch('updatePersonaBoard');
        $this->dispatch('notice', [
            'type' => 'success',
            'message' => $this->persona ? 'Persona erfolgreich gespeichert!' : 'Persona erfolgreich erstellt!'
        ]);
        $this->modalShow = false;
    }

    public function render()
    {
        $toneOfVoiceBoards = collect();
        if ($this->personaBoardId) {
            $board = BrandsPersonaBoard::find($this->personaBoardId);
            if ($board) {
                $toneOfVoiceBoards = BrandsToneOfVoiceBoard::where('brand_id', $board->brand_id)
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('brands::livewire.persona-modal', [
            'toneOfVoiceBoards' => $toneOfVoiceBoards,
        ])->layout('platform::layouts.app');
    }
}
