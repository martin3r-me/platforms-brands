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
    public $contentType;

    #[On('open-modal-content-board-block-settings')] 
    public function openModalContentBoardBlockSettings($blockId)
    {
        $this->block = BrandsContentBoardBlock::with('row.section.contentBoard')->findOrFail($blockId);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->block->row->section->contentBoard);
        
        $this->span = $this->block->span;
        $this->name = $this->block->name;
        $this->description = $this->block->description;
        $this->contentType = $this->block->content_type;
        
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
            'contentType' => 'nullable|string|in:text,image,carousel,video',
        ];
    }

    public function setContentType($type)
    {
        if (!$this->block) {
            return;
        }
        
        $this->authorize('update', $this->block->row->section->contentBoard);
        
        // Wenn bereits ein Content existiert, löschen
        if ($this->block->content) {
            $this->block->content->delete();
        }
        
        // Neuen Content erstellen, wenn Typ gesetzt wird
        if ($type === 'text') {
            $user = Auth::user();
            $team = $user->currentTeam;
            
            $textContent = \Platform\Brands\Models\BrandsContentBoardBlockText::create([
                'content' => '',
                'user_id' => $user->id,
                'team_id' => $team->id,
            ]);
            
            $this->block->content_type = 'text';
            $this->block->content_id = $textContent->id;
            $this->block->save();
            
            $this->contentType = 'text';
        } else {
            $this->block->content_type = $type;
            $this->block->content_id = null;
            $this->block->save();
            
            $this->contentType = $type;
        }
        
        $this->dispatch('updateContentBoard');
        $this->dispatch('updateSection');
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
        
        // Block-Informationen für Notification speichern, bevor reset()
        // Direkt die ID und Klasse verwenden, um sicherzustellen, dass sie gesetzt sind
        $blockId = $this->block->id;
        $blockClass = \Platform\Brands\Models\BrandsContentBoardBlock::class;
        
        $this->dispatch('updateContentBoard');
        $this->dispatch('updateSection');

        $this->dispatch('notifications:store', [
            'title' => 'Block gespeichert',
            'message' => 'Der Block wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => $blockClass,
            'noticable_id'   => $blockId,
        ]);

        $this->reset(['span', 'name', 'description']);
        $this->closeModal();
    }

    public function deleteBlock()
    {
        if (!$this->block) {
            return;
        }
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->block->row->section->contentBoard);
        
        // Block-Informationen für Notification speichern, bevor delete()
        $blockId = $this->block->id;
        $blockClass = \Platform\Brands\Models\BrandsContentBoardBlock::class;
        
        $this->block->delete();
        
        $this->dispatch('updateContentBoard');
        $this->dispatch('updateSection');

        $this->dispatch('notifications:store', [
            'title' => 'Block gelöscht',
            'message' => 'Der Block wurde erfolgreich gelöscht.',
            'notice_type' => 'success',
            'noticable_type' => $blockClass,
            'noticable_id'   => $blockId,
        ]);

        $this->reset(['span', 'name', 'description', 'contentType']);
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
