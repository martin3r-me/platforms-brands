<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class ContentBoardBlockSettingsModal extends Component
{
    public $modalShow = false;
    public $block;
    public $span;
    public $name;
    public $description;

    #[On('open-modal-content-board-block-settings')] 
    public function openModalContentBoardBlockSettings($blockId)
    {
        $this->block = BrandsContentBoardBlock::with('row.section.contentBoard')->findOrFail($blockId);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->block->row->section->contentBoard);
        
        $this->span = $this->block->span;
        $this->name = $this->block->name;
        $this->description = $this->block->description;
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'span' => 'required|integer|min:1|max:12',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        
        if (!$this->block) {
            return;
        }
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->block->row->section->contentBoard);

        $row = $this->block->row;
        $row->load('blocks');
        
        // Berechne die aktuelle Summe aller Spans in dieser Row
        $currentSum = $row->blocks->sum('span');
        $currentBlockSpan = $this->block->span;
        $newSum = $currentSum - $currentBlockSpan + $this->span;
        
        // Prüfe, ob die neue Summe 12 nicht überschreitet
        if ($newSum > 12) {
            $this->addError('span', "Die Summe aller Spans in einer Row darf nicht mehr als 12 betragen. Aktuell: {$currentSum}, mit neuem Wert: {$newSum}.");
            return;
        }
        
        $this->block->span = $this->span;
        $this->block->name = trim($this->name);
        $this->block->description = $this->description ? trim($this->description) : null;
        $this->block->save();
        
        $this->dispatch('updateContentBoard');
        $this->dispatch('updateSection');

        $this->dispatch('notifications:store', [
            'title' => 'Block gespeichert',
            'message' => 'Der Block wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->block),
            'noticable_id'   => $this->block->getKey(),
        ]);

        $this->reset(['block', 'span', 'name', 'description']);
        $this->closeModal();
    }

    public function deleteBlock()
    {
        if (!$this->block) {
            return;
        }
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->block->row->section->contentBoard);
        
        $this->block->delete();
        
        $this->dispatch('updateContentBoard');
        $this->dispatch('updateSection');

        $this->dispatch('notifications:store', [
            'title' => 'Block gelöscht',
            'message' => 'Der Block wurde erfolgreich gelöscht.',
            'notice_type' => 'success',
        ]);

        $this->reset(['block', 'span', 'name', 'description']);
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.content-board-block-settings-modal')->layout('platform::layouts.app');
    }
}
