<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Platform\Brands\Models\BrandsToneOfVoiceDimension;
use Livewire\Attributes\On;

class ToneOfVoiceDimensionModal extends Component
{
    public $modalShow = false;
    public $dimension;
    public $toneOfVoiceBoardId;

    // Dimension fields
    public $dimensionName = '';
    public $dimensionLabelLeft = '';
    public $dimensionLabelRight = '';
    public $dimensionValue = 50;
    public $dimensionDescription = '';

    #[On('open-modal-tone-of-voice-dimension')]
    public function openModal($toneOfVoiceBoardId, $dimensionId = null)
    {
        $this->toneOfVoiceBoardId = $toneOfVoiceBoardId;
        $this->resetFields();

        if ($dimensionId) {
            $this->dimension = BrandsToneOfVoiceDimension::findOrFail($dimensionId);
            $this->fillFromDimension();
        } else {
            $this->dimension = null;
        }

        $this->modalShow = true;
    }

    protected function resetFields()
    {
        $this->dimensionName = '';
        $this->dimensionLabelLeft = '';
        $this->dimensionLabelRight = '';
        $this->dimensionValue = 50;
        $this->dimensionDescription = '';
    }

    protected function fillFromDimension()
    {
        $this->dimensionName = $this->dimension->name;
        $this->dimensionLabelLeft = $this->dimension->label_left;
        $this->dimensionLabelRight = $this->dimension->label_right;
        $this->dimensionValue = $this->dimension->value;
        $this->dimensionDescription = $this->dimension->description ?? '';
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'dimensionName' => 'required|string|max:255',
            'dimensionLabelLeft' => 'required|string|max:255',
            'dimensionLabelRight' => 'required|string|max:255',
            'dimensionValue' => 'required|integer|min:0|max:100',
        ];
    }

    public function save()
    {
        $this->validate();

        $board = BrandsToneOfVoiceBoard::findOrFail($this->toneOfVoiceBoardId);
        $this->authorize('update', $board);

        $data = [
            'name' => $this->dimensionName,
            'label_left' => $this->dimensionLabelLeft,
            'label_right' => $this->dimensionLabelRight,
            'value' => (int) $this->dimensionValue,
            'description' => $this->dimensionDescription ?: null,
        ];

        if ($this->dimension) {
            $this->dimension->update($data);
        } else {
            $data['tone_of_voice_board_id'] = $this->toneOfVoiceBoardId;
            BrandsToneOfVoiceDimension::create($data);
        }

        $this->dispatch('updateToneOfVoiceBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.tone-of-voice-dimension-modal')->layout('platform::layouts.app');
    }
}
