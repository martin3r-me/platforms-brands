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

    public function generateDummyText($type, $count)
    {
        $lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
        
        $words = explode(' ', $lorem);
        $generated = '';
        
        switch ($type) {
            case 'words':
                // Generiere X Wörter
                $wordCount = min($count, 1000); // Max 1000 Wörter
                $generated = implode(' ', array_slice($words, 0, $wordCount));
                break;
                
            case 'chars':
                // Generiere X Zeichen
                $charCount = min($count, 10000); // Max 10000 Zeichen
                $text = $lorem;
                while (strlen($text) < $charCount) {
                    $text .= ' ' . $lorem;
                }
                $generated = substr($text, 0, $charCount);
                break;
                
            case 'paragraphs':
                // Generiere X Absätze
                $paragraphCount = min($count, 50); // Max 50 Absätze
                for ($i = 0; $i < $paragraphCount; $i++) {
                    $generated .= $lorem;
                    if ($i < $paragraphCount - 1) {
                        $generated .= "\n\n";
                    }
                }
                break;
                
            case 'sentences':
                // Generiere X Sätze
                $sentenceCount = min($count, 200); // Max 200 Sätze
                $sentences = [
                    'Lorem ipsum dolor sit amet.',
                    'Consectetur adipiscing elit.',
                    'Sed do eiusmod tempor incididunt.',
                    'Ut labore et dolore magna aliqua.',
                    'Duis aute irure dolor in reprehenderit.',
                    'Excepteur sint occaecat cupidatat non proident.',
                ];
                for ($i = 0; $i < $sentenceCount; $i++) {
                    $generated .= $sentences[$i % count($sentences)] . ' ';
                }
                $generated = trim($generated);
                break;
        }
        
        // Editor aktualisieren - Text wird am Ende eingefügt
        $this->dispatch('content-block-insert-text', [
            'blockId' => $this->block->id,
            'text' => $generated,
        ]);
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
