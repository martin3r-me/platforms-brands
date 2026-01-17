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
            
            // Redirect Domain aus Brands Config verwenden
            $redirectDomain = config('brands.meta.redirect_domain');
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
            
            // Meta OAuth Credentials aus Brands Config
            $clientId = config('brands.meta.client_id');
            $clientSecret = config('brands.meta.client_secret');
            
            if (!$clientId || !$clientSecret) {
                return 'Fehler: Meta OAuth ist nicht konfiguriert. Bitte konfiguriere META_CLIENT_ID und META_CLIENT_SECRET in der .env Datei.';
            }
            
            // State generieren (nur für Anzeige, wird im Controller neu generiert)
            $state = Str::random(32);
            
            // API Version für OAuth URL
            $apiVersion = config('brands.meta.api_version', 'v21.0');
            
            // Facebook OAuth URL generieren
            $provider = Socialite::buildProvider(
                \Laravel\Socialite\Two\FacebookProvider::class,
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect' => $redirectUri,
                ]
            );
            
            // API Version setzen, falls die Methode existiert
            if (method_exists($provider, 'setApiVersion')) {
                $provider->setApiVersion($apiVersion);
            } elseif (method_exists($provider, 'version')) {
                $provider->version($apiVersion);
            }
            
            $facebookUrl = $provider
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
            
            // Falls Socialite die Version nicht unterstützt, manuell in URL ersetzen
            if (strpos($facebookUrl, '/v') !== false && strpos($facebookUrl, $apiVersion) === false) {
                // Ersetze die Version in der URL
                $facebookUrl = preg_replace('/\/v\d+\.\d+\//', '/' . $apiVersion . '/', $facebookUrl);
            }
            
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
