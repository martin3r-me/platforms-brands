<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsBrand;
use Livewire\Attributes\On;

class BrandSettingsModal extends Component
{
    public $modalShow = false;
    public $brand;

    #[On('open-modal-brand-settings')]
    public function openModalBrandSettings($brandId)
    {
        $this->brand = BrandsBrand::findOrFail($brandId);

        // Policy-Berechtigung prüfen - Settings erfordert view-Rechte
        $this->authorize('settings', $this->brand);

        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'brand.name' => 'required|string|max:255',
            'brand.description' => 'nullable|string',
            'brand.live_url' => 'nullable|string|max:2048',
            'brand.wireframe_url' => 'nullable|string|max:2048',
            'brand.mockup_url' => 'nullable|string|max:2048',
        ];
    }

    public function save()
    {
        $this->validate();

        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->brand);

        $this->brand->save();
        $this->brand->refresh();

        $this->dispatch('updateSidebar');
        $this->dispatch('updateBrand');
        $this->dispatch('updateDashboard');

        $this->dispatch('notifications:store', [
            'title' => 'Marke gespeichert',
            'message' => 'Die Marke wurde erfolgreich aktualisiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->brand),
            'noticable_id'   => $this->brand->getKey(),
        ]);

        $this->closeModal();
    }

    public function markAsDone()
    {
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->brand);
        
        $this->brand->done = true;
        $this->brand->done_at = now();
        $this->brand->save();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updateBrand');
        $this->dispatch('updateDashboard');
        
        $this->dispatch('notifications:store', [
            'title' => 'Marke abgeschlossen',
            'message' => 'Die Marke wurde erfolgreich als abgeschlossen markiert.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->brand),
            'noticable_id'   => $this->brand->getKey(),
        ]);
        
        $this->brand->refresh();
    }

    public function deleteBrand()
    {
        // Policy-Berechtigung prüfen
        $this->authorize('delete', $this->brand);
        
        $this->brand->delete();
        // Nach Brands-Dashboard leiten
        $this->redirect(route('brands.dashboard'), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.brand-settings-modal')->layout('platform::layouts.app');
    }
}
