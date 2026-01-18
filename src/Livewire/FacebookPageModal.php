<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsBrand;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class FacebookPageModal extends Component
{
    public $modalShow = false;
    public $brand;

    #[Computed]
    public function oauthRedirectUrl()
    {
        // OAuth läuft jetzt über das Integrations-Modul (user-zentriert)
        return route('integrations.oauth2.start', ['integrationKey' => 'meta']);
    }

    #[Computed]
    public function facebookOAuthUrl()
    {
        // OAuth läuft jetzt über das Integrations-Modul
        // Die URL wird im OAuth2Controller generiert
        return route('integrations.oauth2.start', ['integrationKey' => 'meta']);
    }

    #[On('open-modal-facebook-page')] 
    public function openModalFacebookPage($brandId)
    {
        $this->brand = BrandsBrand::findOrFail($brandId);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $this->brand);
        
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
        
        // Weiterleitung zum Integrations OAuth2 Controller
        return $this->redirect(route('integrations.oauth2.start', ['integrationKey' => 'meta']));
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
