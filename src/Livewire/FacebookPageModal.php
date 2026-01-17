<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsBrand;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Platform\MetaOAuth\Services\MetaOAuthService;

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
            ]);
            return;
        }
        
        $this->resetForm();
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
        
        // Prüfen, ob bereits eine Facebook Page existiert (nur eine erlaubt)
        if ($this->brand->facebookPages()->exists()) {
            $this->dispatch('notifications:store', [
                'title' => 'Facebook Page bereits verknüpft',
                'message' => 'Es ist bereits eine Facebook Page mit dieser Marke verknüpft.',
                'notice_type' => 'error',
            ]);
            $this->closeModal();
            return;
        }
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            $this->dispatch('notifications:store', [
                'title' => 'Fehler',
                'message' => 'Kein Team ausgewählt.',
                'notice_type' => 'error',
            ]);
            return;
        }

        // Brand ID und Team ID in Session speichern für Callback
        session([
            'brands_oauth_brand_id' => $this->brand->id,
            'brands_oauth_team_id' => $team->id,
        ]);

        // OAuth-Flow starten
        $metaOAuthService = app(MetaOAuthService::class);
        $redirectUrl = $metaOAuthService->getRedirectUrl([
            'business_management',
            'pages_read_engagement',
            'pages_read_user_content',
            'pages_manage_posts',
            'pages_show_list',
            'instagram_basic',
            'instagram_manage_insights',
        ], null, route('brands.facebook-pages.oauth.callback'));

        // Modal schließen und zu OAuth weiterleiten
        $this->closeModal();
        
        return redirect($redirectUrl);
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
