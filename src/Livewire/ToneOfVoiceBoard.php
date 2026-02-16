<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Platform\Brands\Models\BrandsToneOfVoiceEntry;
use Platform\Brands\Models\BrandsToneOfVoiceDimension;
use Livewire\Attributes\On;

class ToneOfVoiceBoard extends Component
{
    public BrandsToneOfVoiceBoard $toneOfVoiceBoard;

    public function mount(BrandsToneOfVoiceBoard $brandsToneOfVoiceBoard)
    {
        $this->toneOfVoiceBoard = $brandsToneOfVoiceBoard->fresh()->load(['entries', 'dimensions']);
        $this->authorize('view', $this->toneOfVoiceBoard);
    }

    #[On('updateToneOfVoiceBoard')]
    public function updateToneOfVoiceBoard()
    {
        $this->toneOfVoiceBoard->refresh();
        $this->toneOfVoiceBoard->load(['entries', 'dimensions']);
    }

    public function createEntry()
    {
        $this->authorize('update', $this->toneOfVoiceBoard);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        BrandsToneOfVoiceEntry::create([
            'tone_of_voice_board_id' => $this->toneOfVoiceBoard->id,
            'name' => 'Neuer Messaging-Eintrag',
            'type' => 'core_message',
            'content' => '',
        ]);

        $this->toneOfVoiceBoard->refresh();
        $this->toneOfVoiceBoard->load(['entries', 'dimensions']);
    }

    public function deleteEntry($entryId)
    {
        $this->authorize('update', $this->toneOfVoiceBoard);

        $entry = BrandsToneOfVoiceEntry::findOrFail($entryId);
        $entry->delete();

        $this->toneOfVoiceBoard->refresh();
        $this->toneOfVoiceBoard->load(['entries', 'dimensions']);
    }

    public function createDimension()
    {
        $this->authorize('update', $this->toneOfVoiceBoard);

        BrandsToneOfVoiceDimension::create([
            'tone_of_voice_board_id' => $this->toneOfVoiceBoard->id,
            'name' => 'Neue Dimension',
            'label_left' => 'Links',
            'label_right' => 'Rechts',
            'value' => 50,
        ]);

        $this->toneOfVoiceBoard->refresh();
        $this->toneOfVoiceBoard->load(['entries', 'dimensions']);
    }

    public function deleteDimension($dimensionId)
    {
        $this->authorize('update', $this->toneOfVoiceBoard);

        $dimension = BrandsToneOfVoiceDimension::findOrFail($dimensionId);
        $dimension->delete();

        $this->toneOfVoiceBoard->refresh();
        $this->toneOfVoiceBoard->load(['entries', 'dimensions']);
    }

    public function updateDimensionValue($dimensionId, $value)
    {
        $this->authorize('update', $this->toneOfVoiceBoard);

        $dimension = BrandsToneOfVoiceDimension::findOrFail($dimensionId);
        $dimension->update(['value' => max(0, min(100, (int) $value))]);

        $this->toneOfVoiceBoard->refresh();
        $this->toneOfVoiceBoard->load(['entries', 'dimensions']);
    }

    public function updateEntryOrder($groups)
    {
        $this->authorize('update', $this->toneOfVoiceBoard);

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $entry = BrandsToneOfVoiceEntry::find($item['value']);
                if ($entry) {
                    $entry->order = $item['order'];
                    $entry->save();
                }
            }
        }

        $this->toneOfVoiceBoard->refresh();
        $this->toneOfVoiceBoard->load(['entries', 'dimensions']);
    }

    public function render()
    {
        $user = Auth::user();
        $entries = $this->toneOfVoiceBoard->entries()->orderBy('order')->get();
        $dimensions = $this->toneOfVoiceBoard->dimensions()->orderBy('order')->get();

        return view('brands::livewire.tone-of-voice-board', [
            'user' => $user,
            'entries' => $entries,
            'dimensions' => $dimensions,
        ])->layout('platform::layouts.app');
    }
}
