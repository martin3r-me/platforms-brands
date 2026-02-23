<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;

class IntakeBlockDefinitionModal extends Component
{
    public ?int $definitionId = null;
    public string $name = '';
    public string $description = '';
    public string $block_type = 'text';
    public string $ai_prompt = '';
    public bool $is_active = true;

    public function mount(?int $definitionId = null)
    {
        if ($definitionId) {
            $def = BrandsIntakeBlockDefinition::findOrFail($definitionId);
            $this->definitionId = $def->id;
            $this->name = $def->name ?? '';
            $this->description = $def->description ?? '';
            $this->block_type = $def->block_type ?? 'text';
            $this->ai_prompt = $def->ai_prompt ?? '';
            $this->is_active = $def->is_active;
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'block_type' => 'required|string',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'block_type' => $this->block_type,
            'ai_prompt' => $this->ai_prompt ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->definitionId) {
            $def = BrandsIntakeBlockDefinition::findOrFail($this->definitionId);
            $this->authorize('update', $def);
            $def->update($data);
        } else {
            $data['user_id'] = auth()->id();
            $data['team_id'] = auth()->user()->currentTeam->id;
            BrandsIntakeBlockDefinition::create($data);
        }

        $this->dispatch('definition-saved');
    }

    public function render()
    {
        return view('brands::livewire.intake-block-definition-modal', [
            'blockTypes' => BrandsIntakeBlockDefinition::getBlockTypes(),
        ]);
    }
}
