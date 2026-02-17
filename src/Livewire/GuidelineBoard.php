<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsGuidelineBoard;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Platform\Brands\Models\BrandsGuidelineEntry;
use Livewire\Attributes\On;

class GuidelineBoard extends Component
{
    public BrandsGuidelineBoard $guidelineBoard;

    public function mount(BrandsGuidelineBoard $brandsGuidelineBoard)
    {
        $this->guidelineBoard = $brandsGuidelineBoard->fresh()->load(['chapters.entries']);
        $this->authorize('view', $this->guidelineBoard);
    }

    #[On('updateGuidelineBoard')]
    public function updateGuidelineBoard()
    {
        $this->guidelineBoard->refresh();
        $this->guidelineBoard->load(['chapters.entries']);
    }

    public function createChapter()
    {
        $this->authorize('update', $this->guidelineBoard);

        BrandsGuidelineChapter::create([
            'guideline_board_id' => $this->guidelineBoard->id,
            'title' => 'Neues Kapitel',
        ]);

        $this->guidelineBoard->refresh();
        $this->guidelineBoard->load(['chapters.entries']);
    }

    public function deleteChapter($chapterId)
    {
        $this->authorize('update', $this->guidelineBoard);

        $chapter = BrandsGuidelineChapter::findOrFail($chapterId);
        $chapter->delete();

        $this->guidelineBoard->refresh();
        $this->guidelineBoard->load(['chapters.entries']);
    }

    public function createEntry($chapterId)
    {
        $this->authorize('update', $this->guidelineBoard);

        BrandsGuidelineEntry::create([
            'guideline_chapter_id' => $chapterId,
            'title' => 'Neue Regel',
            'rule_text' => '',
        ]);

        $this->guidelineBoard->refresh();
        $this->guidelineBoard->load(['chapters.entries']);
    }

    public function deleteEntry($entryId)
    {
        $this->authorize('update', $this->guidelineBoard);

        $entry = BrandsGuidelineEntry::findOrFail($entryId);
        $entry->delete();

        $this->guidelineBoard->refresh();
        $this->guidelineBoard->load(['chapters.entries']);
    }

    public function updateChapterOrder($groups)
    {
        $this->authorize('update', $this->guidelineBoard);

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $chapter = BrandsGuidelineChapter::find($item['value']);
                if ($chapter) {
                    $chapter->order = $item['order'];
                    $chapter->save();
                }
            }
        }

        $this->guidelineBoard->refresh();
        $this->guidelineBoard->load(['chapters.entries']);
    }

    public function render()
    {
        $user = Auth::user();
        $chapters = $this->guidelineBoard->chapters()->with('entries')->orderBy('order')->get();

        // Alle Boards der gleichen Marke für Cross-Referenzen laden
        $brand = $this->guidelineBoard->brand;
        $availableBoards = [];
        if ($brand) {
            foreach (['ciBoards', 'logoBoards', 'typographyBoards', 'toneOfVoiceBoards', 'personaBoards', 'competitorBoards'] as $relation) {
                foreach ($brand->$relation as $board) {
                    $availableBoards[] = [
                        'type' => $relation,
                        'id' => $board->id,
                        'name' => $board->name,
                    ];
                }
            }
        }

        return view('brands::livewire.guideline-board', [
            'user' => $user,
            'chapters' => $chapters,
            'availableBoards' => $availableBoards,
        ])->layout('platform::layouts.app');
    }
}
