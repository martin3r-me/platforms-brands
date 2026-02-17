<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsGuidelineBoard;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Platform\Brands\Models\BrandsGuidelineEntry;
use Livewire\Attributes\On;

class GuidelineEntryModal extends Component
{
    public $modalShow = false;
    public $entry;
    public $guidelineBoardId;
    public $chapterId;

    // Entry fields
    public $entryTitle = '';
    public $entryRuleText = '';
    public $entryRationale = '';
    public $entryDoExample = '';
    public $entryDontExample = '';
    public $entryCrossReferences = [];

    // Available boards for cross-references
    public $availableBoards = [];

    #[On('open-modal-guideline-entry')]
    public function openModal($guidelineBoardId, $chapterId, $entryId = null)
    {
        $this->guidelineBoardId = $guidelineBoardId;
        $this->chapterId = $chapterId;
        $this->resetFields();
        $this->loadAvailableBoards();

        if ($entryId) {
            $this->entry = BrandsGuidelineEntry::findOrFail($entryId);
            $this->fillFromEntry();
        } else {
            $this->entry = null;
        }

        $this->modalShow = true;
    }

    protected function loadAvailableBoards()
    {
        $board = BrandsGuidelineBoard::find($this->guidelineBoardId);
        $this->availableBoards = [];

        if ($board && $board->brand) {
            $brand = $board->brand;
            $boardTypes = [
                'ciBoards' => ['type' => 'ci-board', 'label' => 'CI Board'],
                'logoBoards' => ['type' => 'logo-board', 'label' => 'Logo Board'],
                'typographyBoards' => ['type' => 'typography-board', 'label' => 'Typografie Board'],
                'toneOfVoiceBoards' => ['type' => 'tone-of-voice-board', 'label' => 'Tone of Voice Board'],
                'personaBoards' => ['type' => 'persona-board', 'label' => 'Persona Board'],
                'competitorBoards' => ['type' => 'competitor-board', 'label' => 'Wettbewerber Board'],
            ];

            foreach ($boardTypes as $relation => $meta) {
                foreach ($brand->$relation as $b) {
                    $this->availableBoards[] = [
                        'board_type' => $meta['type'],
                        'board_id' => $b->id,
                        'label' => $meta['label'] . ': ' . $b->name,
                    ];
                }
            }
        }
    }

    protected function resetFields()
    {
        $this->entryTitle = '';
        $this->entryRuleText = '';
        $this->entryRationale = '';
        $this->entryDoExample = '';
        $this->entryDontExample = '';
        $this->entryCrossReferences = [];
    }

    protected function fillFromEntry()
    {
        $this->entryTitle = $this->entry->title;
        $this->entryRuleText = $this->entry->rule_text;
        $this->entryRationale = $this->entry->rationale ?? '';
        $this->entryDoExample = $this->entry->do_example ?? '';
        $this->entryDontExample = $this->entry->dont_example ?? '';
        $this->entryCrossReferences = $this->entry->cross_references ?? [];
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'entryTitle' => 'required|string|max:255',
            'entryRuleText' => 'required|string',
        ];
    }

    public function addCrossReference()
    {
        $this->entryCrossReferences[] = ['board_type' => '', 'board_id' => '', 'label' => ''];
    }

    public function removeCrossReference($index)
    {
        unset($this->entryCrossReferences[$index]);
        $this->entryCrossReferences = array_values($this->entryCrossReferences);
    }

    public function save()
    {
        $this->validate();

        $board = BrandsGuidelineBoard::findOrFail($this->guidelineBoardId);
        $this->authorize('update', $board);

        // Filter empty cross-references
        $crossRefs = array_values(array_filter($this->entryCrossReferences, function ($ref) {
            return !empty($ref['board_type']) && !empty($ref['board_id']);
        }));

        $data = [
            'title' => $this->entryTitle,
            'rule_text' => $this->entryRuleText,
            'rationale' => $this->entryRationale ?: null,
            'do_example' => $this->entryDoExample ?: null,
            'dont_example' => $this->entryDontExample ?: null,
            'cross_references' => !empty($crossRefs) ? $crossRefs : null,
        ];

        if ($this->entry) {
            $this->entry->update($data);
        } else {
            $data['guideline_chapter_id'] = $this->chapterId;
            BrandsGuidelineEntry::create($data);
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
        return view('brands::livewire.guideline-entry-modal')->layout('platform::layouts.app');
    }
}
