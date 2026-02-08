<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsKanbanCard;
use Livewire\Attributes\On;

class KanbanCard extends Component
{
    public BrandsKanbanCard $card;
    public string $title = '';
    public string $description = '';

    public function mount(BrandsKanbanCard $brandsKanbanCard)
    {
        $this->card = $brandsKanbanCard->fresh()->load('slot', 'kanbanBoard.brand');

        // Berechtigung prüfen
        $this->authorize('view', $this->card);

        $this->title = $this->card->title ?? '';
        $this->description = $this->card->description ?? '';
    }

    #[On('updateKanbanCard')]
    public function updateKanbanCard()
    {
        $this->card->refresh();
        $this->title = $this->card->title ?? '';
        $this->description = $this->card->description ?? '';
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        $this->authorize('update', $this->card);

        $this->card->update([
            'title' => $this->title,
            'description' => $this->description,
        ]);

        $this->card->refresh();
        $this->title = $this->card->title ?? '';

        // UI can show "saved"
        $this->dispatch('brands-kanban-saved', [
            'cardId' => $this->card->id,
            'savedAt' => now()->toIso8601String(),
        ]);

        // Navbar Title aktualisieren
        $this->dispatch('updateSidebar');
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.kanban-card', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
