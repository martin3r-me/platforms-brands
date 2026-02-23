<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;

class IntakeBlockDefinitionIndex extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    public function delete(int $id)
    {
        $def = BrandsIntakeBlockDefinition::findOrFail($id);
        $this->authorize('delete', $def);
        $def->delete();
    }

    public function openCreate()
    {
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id)
    {
        $this->editingId = $id;
        $this->showModal = true;
    }

    #[On('definition-saved')]
    public function closeModal()
    {
        $this->showModal = false;
        $this->editingId = null;
    }

    public function render()
    {
        $definitions = BrandsIntakeBlockDefinition::where('team_id', auth()->user()->currentTeam?->id)
            ->orderBy('name')
            ->get();

        return view('brands::livewire.intake-block-definition-index', [
            'definitions' => $definitions,
        ]);
    }
}
