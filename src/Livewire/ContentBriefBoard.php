<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Livewire\Attributes\On;

class ContentBriefBoard extends Component
{
    public BrandsContentBriefBoard $contentBriefBoard;

    // Inline-Editing
    public bool $editing = false;
    public string $editName = '';
    public string $editDescription = '';
    public string $editContentType = '';
    public string $editSearchIntent = '';
    public string $editStatus = '';
    public string $editTargetSlug = '';
    public ?int $editTargetWordCount = null;

    public function mount(BrandsContentBriefBoard $brandsContentBriefBoard)
    {
        $this->contentBriefBoard = $brandsContentBriefBoard->fresh()->load('brand', 'seoBoard');
        $this->authorize('view', $this->contentBriefBoard);
    }

    #[On('updateContentBriefBoard')]
    public function updateContentBriefBoard()
    {
        $this->contentBriefBoard->refresh();
        $this->contentBriefBoard->load('brand', 'seoBoard');
    }

    public function startEditing()
    {
        $this->authorize('update', $this->contentBriefBoard);

        $this->editing = true;
        $this->editName = $this->contentBriefBoard->name;
        $this->editDescription = $this->contentBriefBoard->description ?? '';
        $this->editContentType = $this->contentBriefBoard->content_type;
        $this->editSearchIntent = $this->contentBriefBoard->search_intent;
        $this->editStatus = $this->contentBriefBoard->status;
        $this->editTargetSlug = $this->contentBriefBoard->target_slug ?? '';
        $this->editTargetWordCount = $this->contentBriefBoard->target_word_count;
    }

    public function saveEditing()
    {
        $this->authorize('update', $this->contentBriefBoard);

        $this->validate([
            'editName' => 'required|string|max:255',
            'editDescription' => 'nullable|string',
            'editContentType' => 'required|in:' . implode(',', array_keys(BrandsContentBriefBoard::CONTENT_TYPES)),
            'editSearchIntent' => 'required|in:' . implode(',', array_keys(BrandsContentBriefBoard::SEARCH_INTENTS)),
            'editStatus' => 'required|in:' . implode(',', array_keys(BrandsContentBriefBoard::STATUSES)),
            'editTargetSlug' => 'nullable|string|max:255',
            'editTargetWordCount' => 'nullable|integer|min:0',
        ]);

        $this->contentBriefBoard->update([
            'name' => $this->editName,
            'description' => $this->editDescription ?: null,
            'content_type' => $this->editContentType,
            'search_intent' => $this->editSearchIntent,
            'status' => $this->editStatus,
            'target_slug' => $this->editTargetSlug ?: null,
            'target_word_count' => $this->editTargetWordCount,
        ]);

        $this->editing = false;
        $this->updateContentBriefBoard();

        $this->dispatch('notifications:store', [
            'title' => 'Content Brief aktualisiert',
            'message' => "'{$this->contentBriefBoard->name}' wurde gespeichert.",
            'notice_type' => 'success',
            'noticable_type' => get_class($this->contentBriefBoard),
            'noticable_id' => $this->contentBriefBoard->getKey(),
        ]);
    }

    public function cancelEditing()
    {
        $this->editing = false;
    }

    public function updateStatus(string $status)
    {
        $this->authorize('update', $this->contentBriefBoard);

        if (!array_key_exists($status, BrandsContentBriefBoard::STATUSES)) {
            return;
        }

        $this->contentBriefBoard->update(['status' => $status]);
        $this->updateContentBriefBoard();
    }

    public function deleteBoard()
    {
        $this->authorize('delete', $this->contentBriefBoard);

        $brand = $this->contentBriefBoard->brand;
        $this->contentBriefBoard->delete();

        return $this->redirect(route('brands.brands.show', $brand), navigate: true);
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.content-brief-board', [
            'user' => $user,
            'contentTypes' => BrandsContentBriefBoard::CONTENT_TYPES,
            'searchIntents' => BrandsContentBriefBoard::SEARCH_INTENTS,
            'statuses' => BrandsContentBriefBoard::STATUSES,
        ])->layout('platform::layouts.app');
    }
}
