<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsIntakeBoard;
use Illuminate\Support\Facades\Gate;

class IntakeBoardSettingsModal extends Component
{
    public BrandsIntakeBoard $intakeBoard;
    public string $name = '';
    public string $description = '';
    public string $ai_personality = '';
    public string $industry_context = '';

    public function mount(BrandsIntakeBoard $intakeBoard)
    {
        $this->intakeBoard = $intakeBoard;
        $this->name = $intakeBoard->name ?? '';
        $this->description = $intakeBoard->description ?? '';
        $this->ai_personality = $intakeBoard->ai_personality ?? '';
        $this->industry_context = $intakeBoard->industry_context ?? '';
    }

    public function save()
    {
        Gate::authorize('update', $this->intakeBoard);

        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $this->intakeBoard->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'ai_personality' => $this->ai_personality ?: null,
            'industry_context' => $this->industry_context ?: null,
        ]);

        $this->dispatch('settings-updated');
    }

    public function render()
    {
        return view('brands::livewire.intake-board-settings-modal');
    }
}
