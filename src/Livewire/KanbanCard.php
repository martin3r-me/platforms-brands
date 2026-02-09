<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Livewire\Concerns\WithExtraFields;
use Platform\Brands\Models\BrandsKanbanCard;
use Livewire\Attributes\On;

class KanbanCard extends Component
{
    use WithExtraFields;
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

        // Extra-Felder laden (Definitionen vom Board, Werte von der Card)
        $this->loadExtraFieldValuesFromParent($this->card, $this->card->kanbanBoard);
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
        $this->saveExtraFieldValues($this->card);

        $this->card->refresh();
        $this->title = $this->card->title ?? '';

        // Extra-Felder neu laden (Definitionen vom Board)
        $this->loadExtraFieldValuesFromParent($this->card, $this->card->kanbanBoard);

        // UI can show "saved"
        $this->dispatch('brands-kanban-saved', [
            'cardId' => $this->card->id,
            'savedAt' => now()->toIso8601String(),
        ]);

        // Navbar Title aktualisieren
        $this->dispatch('updateSidebar');
    }

    public function rendered()
    {
        $this->dispatch('extrafields', [
            'context_type' => get_class($this->card->kanbanBoard),
            'context_id' => $this->card->kanbanBoard->id,
        ]);
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.kanban-card', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
