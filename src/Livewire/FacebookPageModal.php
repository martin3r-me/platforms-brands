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
        if (!$this->brand) {
            return null;
        }
        
        return route('brands.facebook-pages.oauth.redirect', ['brand_id' => $this->brand->id]);
    }

    #[Computed]
    public function facebookOAuthUrl()
    {
        if (!$this->brand) {
            return null;
        }

        try {
            // Callback-Route generieren
            $callbackRoute = route('brands.facebook-pages.oauth.callback');
            
            // Redirect Domain aus ENV verwenden, falls gesetzt
            $redirectDomain = env('META_OAUTH_REDIRECT_DOMAIN');
            if ($redirectDomain) {
                // Wenn redirect_domain gesetzt ist, diese verwenden
                if (filter_var($callbackRoute, FILTER_VALIDATE_URL)) {
                    // Absolute URL: nur den Pfad extrahieren
                    $path = parse_url($callbackRoute, PHP_URL_PATH);
                    $redirectUri = rtrim($redirectDomain, '/') . $path;
                } else {
                    // Relative URL: direkt anhängen
                    $redirectUri = rtrim($redirectDomain, '/') . '/' . ltrim($callbackRoute, '/');
                }
            } else {
                // Fallback: absolute URL erstellen
                if (filter_var($callbackRoute, FILTER_VALIDATE_URL)) {
                    $redirectUri = $callbackRoute;
                } else {
                    $redirectUri = url($callbackRoute);
                }
            }
            
            // Meta OAuth Credentials aus services.meta Config (Standard Laravel)
            $clientId = config('services.meta.client_id');
            $clientSecret = config('services.meta.client_secret');
            
            if (!$clientId || !$clientSecret) {
                return 'Fehler: Meta OAuth ist nicht konfiguriert. Bitte konfiguriere META_CLIENT_ID und META_CLIENT_SECRET in der .env Datei und füge sie zu config/services.php hinzu.';
            }
            
            // State generieren (nur für Anzeige, wird im Controller neu generiert)
            $state = Str::random(32);
            
            // Facebook OAuth URL generieren
            $facebookUrl = Socialite::buildProvider(
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
            
            return $facebookUrl;
        } catch (\Exception $e) {
            return 'Fehler: ' . $e->getMessage();
        }
    }

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
