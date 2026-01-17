<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsBrand;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class FacebookPageModal extends Component
{
    public $modalShow = false;
    public $brand;

    #[On('open-modal-facebook-page')] 
    public function openModalFacebookPage($brandId)
    {
        $this->brand = BrandsBrand::findOrFail($brandId);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->brand);
        
        // Prüfen, ob bereits eine Facebook Page existiert (nur eine erlaubt)
        if ($this->brand->facebookPages()->exists()) {
            $this->dispatch('notifications:store', [
                'title' => 'Facebook Page bereits verknüpft',
                'message' => 'Es ist bereits eine Facebook Page mit dieser Marke verknüpft.',
                'notice_type' => 'error',
                'noticable_type' => get_class($this->brand),
                'noticable_id' => $this->brand->getKey(),
            ]);
            return;
        }
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function startOAuth()
    {
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->brand);
        
        // Modal schließen
        $this->closeModal();
        
        // Weiterleitung zum Controller, der den OAuth-Flow startet
        return $this->redirect(route('brands.facebook-pages.oauth.redirect', ['brand_id' => $this->brand->id]));
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('brands::livewire.facebook-page-modal')->layout('platform::layouts.app');
    }
}
