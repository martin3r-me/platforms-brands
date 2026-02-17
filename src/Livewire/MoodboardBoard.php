<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsMoodboardBoard;
use Platform\Brands\Models\BrandsMoodboardImage;
use Livewire\Attributes\On;

class MoodboardBoard extends Component
{
    use WithFileUploads;

    public BrandsMoodboardBoard $moodboardBoard;
    public $newImages = [];

    public function mount(BrandsMoodboardBoard $brandsMoodboardBoard)
    {
        $this->moodboardBoard = $brandsMoodboardBoard->fresh()->load(['images']);
        $this->authorize('view', $this->moodboardBoard);
    }

    #[On('updateMoodboardBoard')]
    public function updateMoodboardBoard()
    {
        $this->moodboardBoard->refresh();
        $this->moodboardBoard->load(['images']);
    }

    public function uploadImages()
    {
        $this->authorize('update', $this->moodboardBoard);

        $this->validate([
            'newImages.*' => 'image|max:10240', // Max 10MB pro Bild
        ]);

        foreach ($this->newImages as $image) {
            $path = $image->store('brands/moodboard/' . $this->moodboardBoard->id, 'public');

            BrandsMoodboardImage::create([
                'moodboard_board_id' => $this->moodboardBoard->id,
                'title' => pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME),
                'image_path' => $path,
                'type' => 'do',
            ]);
        }

        $this->newImages = [];
        $this->moodboardBoard->refresh();
        $this->moodboardBoard->load(['images']);
    }

    public function deleteImage($imageId)
    {
        $this->authorize('update', $this->moodboardBoard);

        $image = BrandsMoodboardImage::findOrFail($imageId);
        $image->delete();

        $this->moodboardBoard->refresh();
        $this->moodboardBoard->load(['images']);
    }

    public function updateImageOrder($groups)
    {
        $this->authorize('update', $this->moodboardBoard);

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $image = BrandsMoodboardImage::find($item['value']);
                if ($image) {
                    $image->order = $item['order'];
                    $image->save();
                }
            }
        }

        $this->moodboardBoard->refresh();
        $this->moodboardBoard->load(['images']);
    }

    public function render()
    {
        $user = Auth::user();
        $doImages = $this->moodboardBoard->images()->where('type', 'do')->orderBy('order')->get();
        $dontImages = $this->moodboardBoard->images()->where('type', 'dont')->orderBy('order')->get();
        $allImages = $this->moodboardBoard->images()->orderBy('order')->get();

        return view('brands::livewire.moodboard-board', [
            'user' => $user,
            'doImages' => $doImages,
            'dontImages' => $dontImages,
            'allImages' => $allImages,
        ])->layout('platform::layouts.app');
    }
}
