<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Livewire\Attributes\On;

class ContentBoardBlockTextEdit extends Component
{
    public BrandsContentBoardBlock $block;
    public $content = '';
    public $name = '';

    public function mount(BrandsContentBoardBlock $brandsContentBoardBlock, string $type)
    {
        $this->block = $brandsContentBoardBlock->load('content', 'row.section.contentBoard');
        
        // Berechtigung prüfen
        $this->authorize('view', $this->block->row->section->contentBoard);
        
        // Prüfen, ob Content-Typ passt
        if ($this->block->content_type !== $type) {
            abort(404);
        }
        
        // Content laden
        if ($this->block->content_type === 'text' && $this->block->content) {
            $this->content = $this->block->content->content ?? '';
        }
        
        $this->name = $this->block->name;
    }

    #[On('updateBlock')] 
    public function updateBlock()
    {
        $this->block->refresh();
        $this->block->load('content');
        
        if ($this->block->content_type === 'text' && $this->block->content) {
            $this->content = $this->block->content->content ?? '';
        }
        
        $this->name = $this->block->name;

        // Editor sync (wire:ignore)
        $this->dispatch('content-block-sync-editor', [
            'blockId' => $this->block->id,
            'name' => $this->name,
            'content' => $this->content,
        ]);
    }

    public function save()
    {
        $this->authorize('update', $this->block->row->section->contentBoard);
        
        if ($this->block->content_type === 'text' && $this->block->content) {
            $this->block->content->update([
                'content' => $this->content,
            ]);
        }
        
        $this->block->refresh();
        $this->block->load('content');

        // Editor sync (wire:ignore) + UI can show "saved"
        $this->dispatch('content-block-saved', [
            'blockId' => $this->block->id,
            'savedAt' => now()->toIso8601String(),
        ]);
        
        // Content Board aktualisieren
        $this->dispatch('updateContentBoard');
    }

    public function getBreadcrumbs()
    {
        $contentBoard = $this->block->row->section->contentBoard;
        $breadcrumbs = [
            ['name' => 'Marken', 'url' => route('brands.brands.index')],
            ['name' => $contentBoard->brand->name, 'url' => route('brands.brands.show', $contentBoard->brand)],
            ['name' => $contentBoard->name, 'url' => route('brands.content-boards.show', $contentBoard)],
        ];

        $breadcrumbs[] = [
            'name' => $this->block->name,
            'url' => route('brands.content-board-blocks.show', ['brandsContentBoardBlock' => $this->block->id, 'type' => $this->block->content_type]),
        ];

        return $breadcrumbs;
    }

    public function getPreviousBlock()
    {
        $allBlocks = BrandsContentBoardBlock::whereHas('row.section', function($q) {
            $q->where('content_board_id', $this->block->row->section->content_board_id);
        })
        ->where('content_type', $this->block->content_type)
        ->where('id', '<', $this->block->id)
        ->orderBy('id', 'desc')
        ->first();
        
        return $allBlocks;
    }

    public function getNextBlock()
    {
        $allBlocks = BrandsContentBoardBlock::whereHas('row.section', function($q) {
            $q->where('content_board_id', $this->block->row->section->content_board_id);
        })
        ->where('content_type', $this->block->content_type)
        ->where('id', '>', $this->block->id)
        ->orderBy('id', 'asc')
        ->first();
        
        return $allBlocks;
    }

    public function getAllBlocks()
    {
        return BrandsContentBoardBlock::whereHas('row.section', function($q) {
            $q->where('content_board_id', $this->block->row->section->content_board_id);
        })
        ->where('content_type', $this->block->content_type)
        ->with('row.section')
        ->orderBy('id', 'asc')
        ->get();
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->block),
            'modelId' => $this->block->id,
            'subject' => $this->block->name,
            'description' => mb_substr(strip_tags($this->block->content->content ?? ''), 0, 100),
            'url' => route('brands.content-board-blocks.show', ['brandsContentBoardBlock' => $this->block->id, 'type' => $this->block->content_type]),
            'source' => 'brands.content-board-block.view',
            'recipients' => [],
            'capabilities' => [
                'manage_channels' => true,
                'threads' => false,
            ],
            'meta' => [
                'created_at' => $this->block->created_at,
            ],
        ]);

        // Organization-Kontext setzen
        $this->dispatch('organization', [
            'context_type' => get_class($this->block),
            'context_id' => $this->block->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
        ]);

        // KeyResult-Kontext setzen
        $this->dispatch('keyresult', [
            'context_type' => get_class($this->block),
            'context_id' => $this->block->id,
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $contentBoard = $this->block->row->section->contentBoard;
        $previousBlock = $this->getPreviousBlock();
        $nextBlock = $this->getNextBlock();
        $allBlocks = $this->getAllBlocks();

        return view('brands::livewire.content-board-block-text-edit', [
            'user' => $user,
            'contentBoard' => $contentBoard,
            'previousBlock' => $previousBlock,
            'nextBlock' => $nextBlock,
            'allBlocks' => $allBlocks,
        ])->layout('platform::layouts.app');
    }
}
