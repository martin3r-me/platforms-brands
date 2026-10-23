<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Database\Eloquent\Model;
use Platform\Core\Livewire\Concerns\HasPageContext;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Livewire\Concerns\WithExtraFields;
use Platform\Brands\Models\BrandsKanbanCard;
use Livewire\Attributes\On;

class KanbanCard extends Component
{
    use WithExtraFields, HasPageContext;
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

        // Extra-Felder laden (geerbte Definitionen vom Board + eigene Werte)
        $this->loadExtraFieldValues($this->card);
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

        // Extra-Felder neu laden
        $this->loadExtraFieldValues($this->card);

        // UI can show "saved"
        $this->dispatch('brands-kanban-saved', [
            'cardId' => $this->card->id,
            'savedAt' => now()->toIso8601String(),
        ]);

        // Navbar Title aktualisieren
        $this->dispatch('updateSidebar');
    }

    protected function pageContext(): ?Model
    {
        return $this->card;
    }

    protected function pageContextOptions(): array
    {
        return [
            'url' => route('brands.kanban-cards.show', $this->card),
            'source' => 'brands.kanban-card.view',
        ];
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.kanban-card', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
