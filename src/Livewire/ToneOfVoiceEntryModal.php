<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Platform\Brands\Models\BrandsToneOfVoiceEntry;
use Livewire\Attributes\On;

class ToneOfVoiceEntryModal extends Component
{
    public $modalShow = false;
    public $entry;
    public $toneOfVoiceBoardId;

    // Entry fields
    public $entryName = '';
    public $entryType = 'core_message';
    public $entryContent = '';
    public $entryDescription = '';
    public $entryExamplePositive = '';
    public $entryExampleNegative = '';

    #[On('open-modal-tone-of-voice-entry')]
    public function openModal($toneOfVoiceBoardId, $entryId = null)
    {
        $this->toneOfVoiceBoardId = $toneOfVoiceBoardId;
        $this->resetFields();

        if ($entryId) {
            $this->entry = BrandsToneOfVoiceEntry::findOrFail($entryId);
            $this->fillFromEntry();
        } else {
            $this->entry = null;
        }

        $this->modalShow = true;
    }

    protected function resetFields()
    {
        $this->entryName = '';
        $this->entryType = 'core_message';
        $this->entryContent = '';
        $this->entryDescription = '';
        $this->entryExamplePositive = '';
        $this->entryExampleNegative = '';
    }

    protected function fillFromEntry()
    {
        $this->entryName = $this->entry->name;
        $this->entryType = $this->entry->type;
        $this->entryContent = $this->entry->content;
        $this->entryDescription = $this->entry->description ?? '';
        $this->entryExamplePositive = $this->entry->example_positive ?? '';
        $this->entryExampleNegative = $this->entry->example_negative ?? '';
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'entryName' => 'required|string|max:255',
            'entryType' => 'required|string|in:' . implode(',', array_keys(BrandsToneOfVoiceEntry::TYPES)),
            'entryContent' => 'required|string',
        ];
    }

    public function save()
    {
        $this->validate();

        $board = BrandsToneOfVoiceBoard::findOrFail($this->toneOfVoiceBoardId);
        $this->authorize('update', $board);

        $data = [
            'name' => $this->entryName,
            'type' => $this->entryType,
            'content' => $this->entryContent,
            'description' => $this->entryDescription ?: null,
            'example_positive' => $this->entryExamplePositive ?: null,
            'example_negative' => $this->entryExampleNegative ?: null,
        ];

        if ($this->entry) {
            $this->entry->update($data);
        } else {
            $data['tone_of_voice_board_id'] = $this->toneOfVoiceBoardId;
            BrandsToneOfVoiceEntry::create($data);
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
        return view('brands::livewire.tone-of-voice-entry-modal')->layout('platform::layouts.app');
    }
}
