<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Brands\Models\BrandsBrand;

class Brand extends Component
{
    public BrandsBrand $brand;

    public function mount(BrandsBrand $brandsBrand)
    {
        $this->brand = $brandsBrand;
        
        // Berechtigung prüfen
        $this->authorize('view', $this->brand);
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->brand),
            'modelId' => $this->brand->id,
            'subject' => $this->brand->name,
            'description' => $this->brand->description ?? '',
            'url' => route('brands.brands.show', $this->brand),
            'source' => 'brands.brand.view',
            'recipients' => [],
            'capabilities' => [
                'manage_channels' => true,
                'threads' => false,
            ],
            'meta' => [
                'created_at' => $this->brand->created_at,
            ],
        ]);

        // Organization-Kontext setzen - beides erlauben: Zeiten + Entity-Verknüpfung + Dimensionen
        $this->dispatch('organization', [
            'context_type' => get_class($this->brand),
            'context_id' => $this->brand->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
        ]);

        // KeyResult-Kontext setzen - ermöglicht Verknüpfung von KeyResults mit dieser Marke
        $this->dispatch('keyresult', [
            'context_type' => get_class($this->brand),
            'context_id' => $this->brand->id,
        ]);
    }

    public function render()
    {
        $user = Auth::user();

        return view('brands::livewire.brand', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
