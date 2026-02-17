<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Platform\Brands\Models\BrandsMoodboardBoard;
use Platform\Brands\Models\BrandsMoodboardImage;
use Livewire\Attributes\On;

class MoodboardImageModal extends Component
{
    use WithFileUploads;

    public $modalShow = false;
    public $image;
    public $moodboardBoardId;

    // Image fields
    public $imageTitle = '';
    public $imageAnnotation = '';
    public $imageTags = [];
    public $imageType = 'do';
    public $imageFile = null;

    // Available tags
    public $availableTags = ['Produkt', 'Lifestyle', 'People', 'Texture', 'Farben', 'Typografie', 'Muster', 'Natur', 'Architektur', 'Abstract'];

    #[On('open-modal-moodboard-image')]
    public function openModal($moodboardBoardId, $imageId = null)
    {
        $this->moodboardBoardId = $moodboardBoardId;
        $this->resetFields();

        if ($imageId) {
            $this->image = BrandsMoodboardImage::findOrFail($imageId);
            $this->fillFromImage();
        } else {
            $this->image = null;
        }

        $this->modalShow = true;
    }

    protected function resetFields()
    {
        $this->imageTitle = '';
        $this->imageAnnotation = '';
        $this->imageTags = [];
        $this->imageType = 'do';
        $this->imageFile = null;
    }

    protected function fillFromImage()
    {
        $this->imageTitle = $this->image->title ?? '';
        $this->imageAnnotation = $this->image->annotation ?? '';
        $this->imageTags = $this->image->tags ?? [];
        $this->imageType = $this->image->type ?? 'do';
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function toggleTag($tag)
    {
        if (in_array($tag, $this->imageTags)) {
            $this->imageTags = array_values(array_diff($this->imageTags, [$tag]));
        } else {
            $this->imageTags[] = $tag;
        }
    }

    public function rules(): array
    {
        $rules = [
            'imageType' => 'required|in:do,dont',
        ];

        if (!$this->image) {
            $rules['imageFile'] = 'required|image|max:10240';
        }

        return $rules;
    }

    public function save()
    {
        $this->validate();

        $board = BrandsMoodboardBoard::findOrFail($this->moodboardBoardId);
        $this->authorize('update', $board);

        $data = [
            'title' => $this->imageTitle ?: null,
            'annotation' => $this->imageAnnotation ?: null,
            'tags' => !empty($this->imageTags) ? $this->imageTags : null,
            'type' => $this->imageType,
        ];

        if ($this->image) {
            // Update
            if ($this->imageFile) {
                $path = $this->imageFile->store('brands/moodboard/' . $this->moodboardBoardId, 'public');
                $data['image_path'] = $path;
            }
            $this->image->update($data);
        } else {
            // Create
            $path = $this->imageFile->store('brands/moodboard/' . $this->moodboardBoardId, 'public');
            $data['moodboard_board_id'] = $this->moodboardBoardId;
            $data['image_path'] = $path;
            if (!$data['title']) {
                $data['title'] = pathinfo($this->imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            }
            BrandsMoodboardImage::create($data);
        }

        $this->dispatch('updateMoodboardBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.moodboard-image-modal')->layout('platform::layouts.app');
    }
}
