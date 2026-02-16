<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Platform\Brands\Models\BrandsTypographyBoard;
use Platform\Brands\Models\BrandsTypographyEntry;
use Livewire\Attributes\On;

class TypographyBoard extends Component
{
    use WithFileUploads;

    public BrandsTypographyBoard $typographyBoard;

    public function mount(BrandsTypographyBoard $brandsTypographyBoard)
    {
        $this->typographyBoard = $brandsTypographyBoard->fresh()->load('entries');
        $this->authorize('view', $this->typographyBoard);
    }

    #[On('updateTypographyBoard')]
    public function updateTypographyBoard()
    {
        $this->typographyBoard->refresh();
        $this->typographyBoard->load('entries');
    }

    public function createEntry()
    {
        $this->authorize('update', $this->typographyBoard);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'Kein Team ausgewählt.');
            return;
        }

        BrandsTypographyEntry::create([
            'typography_board_id' => $this->typographyBoard->id,
            'name' => 'Neue Schrift-Definition',
            'font_family' => 'Inter',
            'font_source' => 'system',
            'font_weight' => 400,
            'font_style' => 'normal',
            'font_size' => 16,
            'line_height' => 1.5,
        ]);

        $this->typographyBoard->refresh();
        $this->typographyBoard->load('entries');
    }

    public function deleteEntry($entryId)
    {
        $this->authorize('update', $this->typographyBoard);

        $entry = BrandsTypographyEntry::findOrFail($entryId);

        // Custom font Datei löschen, falls vorhanden
        if ($entry->font_file_path && Storage::disk('public')->exists($entry->font_file_path)) {
            Storage::disk('public')->delete($entry->font_file_path);
        }

        $entry->delete();

        $this->typographyBoard->refresh();
        $this->typographyBoard->load('entries');
    }

    public function updateEntryOrder($groups)
    {
        $this->authorize('update', $this->typographyBoard);

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $entry = BrandsTypographyEntry::find($item['value']);
                if ($entry) {
                    $entry->order = $item['order'];
                    $entry->save();
                }
            }
        }

        $this->typographyBoard->refresh();
        $this->typographyBoard->load('entries');
    }

    public function render()
    {
        $user = Auth::user();
        $entries = $this->typographyBoard->entries()->orderBy('order')->get();

        return view('brands::livewire.typography-board', [
            'user' => $user,
            'entries' => $entries,
        ])->layout('platform::layouts.app');
    }
}
