<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsGuidelineBoard;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Livewire\Attributes\On;

class GuidelineChapterModal extends Component
{
    public $modalShow = false;
    public $chapter;
    public $guidelineBoardId;

    public $chapterTitle = '';
    public $chapterDescription = '';
    public $chapterIcon = '';

    #[On('open-modal-guideline-chapter')]
    public function openModal($guidelineBoardId, $chapterId = null)
    {
        $this->guidelineBoardId = $guidelineBoardId;
        $this->resetFields();

        if ($chapterId) {
            $this->chapter = BrandsGuidelineChapter::findOrFail($chapterId);
            $this->fillFromChapter();
        } else {
            $this->chapter = null;
        }

        $this->modalShow = true;
    }

    protected function resetFields()
    {
        $this->chapterTitle = '';
        $this->chapterDescription = '';
        $this->chapterIcon = '';
    }

    protected function fillFromChapter()
    {
        $this->chapterTitle = $this->chapter->title;
        $this->chapterDescription = $this->chapter->description ?? '';
        $this->chapterIcon = $this->chapter->icon ?? '';
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'chapterTitle' => 'required|string|max:255',
        ];
    }

    public function save()
    {
        $this->validate();

        $board = BrandsGuidelineBoard::findOrFail($this->guidelineBoardId);
        $this->authorize('update', $board);

        $data = [
            'title' => $this->chapterTitle,
            'description' => $this->chapterDescription ?: null,
            'icon' => $this->chapterIcon ?: null,
        ];

        if ($this->chapter) {
            $this->chapter->update($data);
        } else {
            $data['guideline_board_id'] = $this->guidelineBoardId;
            BrandsGuidelineChapter::create($data);
        }

        $this->dispatch('updateGuidelineBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.guideline-chapter-modal')->layout('platform::layouts.app');
    }
}
