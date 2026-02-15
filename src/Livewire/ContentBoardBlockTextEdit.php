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
        $this->block = $brandsContentBoardBlock->load('content', 'contentBoard');

        // Berechtigung prüfen
        $this->authorize('view', $this->block->contentBoard);
        
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
        $this->authorize('update', $this->block->contentBoard);
        
        // Block-Namen aktualisieren
        if (isset($this->name) && trim($this->name)) {
            $this->block->update([
                'name' => trim($this->name),
            ]);
        }
        
        // Text-Content aktualisieren
        if ($this->block->content_type === 'text' && $this->block->content) {
            $this->block->content->update([
                'content' => $this->content ?? '',
            ]);
        }
        
        $this->block->refresh();
        $this->block->load('content');
        
        // Aktualisiere lokale Werte
        $this->name = $this->block->name;
        if ($this->block->content_type === 'text' && $this->block->content) {
            $this->content = $this->block->content->content ?? '';
        }

        // Editor sync (wire:ignore) + UI can show "saved"
        $this->dispatch('content-block-saved', [
            'blockId' => $this->block->id,
            'savedAt' => now()->toIso8601String(),
        ]);
        
        // Content Board aktualisieren
        $this->dispatch('updateContentBoard');
    }

    public function generateDummyText($wordCount)
    {
        $lorem = 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum';
        
        $words = explode(' ', $lorem);
        $generated = '';
        
        // Generiere genau die angegebene Anzahl Wörter
        for ($i = 0; $i < $wordCount; $i++) {
            $generated .= $words[$i % count($words)];
            if ($i < $wordCount - 1) {
                $generated .= ' ';
            }
        }
        
        // Text am Ende des aktuellen Inhalts einfügen
        $currentContent = $this->content ?? '';
        $separator = $currentContent && trim($currentContent) ? "\n\n" : '';
        $newContent = $currentContent . $separator . $generated;
        
        // Content direkt setzen (wichtig: ohne defer, damit Livewire es sofort hat)
        $this->content = $newContent;
        
        // Editor aktualisieren via Event
        $this->dispatch('content-block-insert-text', [
            'blockId' => $this->block->id,
            'text' => $generated,
            'fullContent' => $newContent,
        ]);
    }

    public function getBreadcrumbs()
    {
        $contentBoard = $this->block->contentBoard;
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
        return BrandsContentBoardBlock::where('content_board_id', $this->block->content_board_id)
            ->where('content_type', $this->block->content_type)
            ->where('order', '<', $this->block->order)
            ->orderBy('order', 'desc')
            ->first();
    }

    public function getNextBlock()
    {
        return BrandsContentBoardBlock::where('content_board_id', $this->block->content_board_id)
            ->where('content_type', $this->block->content_type)
            ->where('order', '>', $this->block->order)
            ->orderBy('order', 'asc')
            ->first();
    }

    public function getAllBlocks()
    {
        return BrandsContentBoardBlock::where('content_board_id', $this->block->content_board_id)
            ->where('content_type', $this->block->content_type)
            ->orderBy('order', 'asc')
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
        $contentBoard = $this->block->contentBoard;
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
