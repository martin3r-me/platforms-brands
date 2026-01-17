<?php

namespace Platform\Brands\Livewire;

use Livewire\Component;
use Platform\Brands\Models\BrandsBrand;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

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
        
        // Prüfen, ob bereits eine Facebook Page existiert (nur eine erlaubt)
        if ($this->brand->facebookPages()->exists()) {
            $this->dispatch('notifications:store', [
                'title' => 'Facebook Page bereits verknüpft',
                'message' => 'Es ist bereits eine Facebook Page mit dieser Marke verknüpft.',
                'notice_type' => 'error',
                'noticable_type' => get_class($this->brand),
                'noticable_id' => $this->brand->getKey(),
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
                'noticable_type' => get_class($this->brand),
                'noticable_id' => $this->brand->getKey(),
            ]);
            return;
        }

        // Brand ID und Team ID in Session speichern für Callback
        session([
            'brands_oauth_brand_id' => $this->brand->id,
            'brands_oauth_team_id' => $team->id,
        ]);

        // OAuth-Flow starten - direkt über Socialite
        $state = Str::random(32);
        session(['meta_oauth_state' => $state]);
        
        $redirectUri = route('brands.facebook-pages.oauth.callback');
        
        // Redirect Domain aus Config verwenden, falls gesetzt
        $redirectDomain = config('meta-oauth.redirect_domain');
        if ($redirectDomain && !filter_var($redirectUri, FILTER_VALIDATE_URL)) {
            $redirectUri = rtrim($redirectDomain, '/') . '/' . ltrim($redirectUri, '/');
        } elseif (!filter_var($redirectUri, FILTER_VALIDATE_URL)) {
            $redirectUri = url($redirectUri);
        }
        
        try {
            // Meta OAuth Credentials aus Config
            $clientId = config('meta-oauth.app_id') ?? config('services.meta.client_id');
            $clientSecret = config('meta-oauth.app_secret') ?? config('services.meta.client_secret');
            
            if (!$clientId || !$clientSecret) {
                $this->dispatch('notifications:store', [
                    'title' => 'OAuth-Fehler',
                    'message' => 'Meta OAuth ist nicht konfiguriert. Bitte konfiguriere META_APP_ID und META_APP_SECRET in der .env Datei.',
                    'notice_type' => 'error',
                    'noticable_type' => get_class($this->brand),
                    'noticable_id' => $this->brand->getKey(),
                ]);
                return;
            }
            
            $redirectUrl = Socialite::buildProvider(
                \Laravel\Socialite\Two\FacebookProvider::class,
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect' => $redirectUri,
                ]
            )
            ->scopes([
                'business_management',
                'pages_read_engagement',
                'pages_read_user_content',
                'pages_manage_posts',
                'pages_show_list',
                'instagram_basic',
                'instagram_manage_insights',
            ])
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();
        } catch (\Exception $e) {
            $this->dispatch('notifications:store', [
                'title' => 'OAuth-Fehler',
                'message' => 'Fehler beim Starten des OAuth-Flows: ' . $e->getMessage(),
                'notice_type' => 'error',
                'noticable_type' => get_class($this->brand),
                'noticable_id' => $this->brand->getKey(),
            ]);
            return;
        }

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
