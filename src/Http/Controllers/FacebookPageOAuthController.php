<?php

namespace Platform\Brands\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Platform\Brands\Models\BrandsBrand;
use Laravel\Socialite\Facades\Socialite;

class FacebookPageOAuthController extends Controller
{
    use AuthorizesRequests;
    /**
     * Startet den OAuth-Flow für Facebook Page Verknüpfung
     */
    public function redirect(Request $request)
    {
        $brandId = $request->query('brand_id');
        
        if (!$brandId) {
            return redirect()->route('brands.dashboard')
                ->with('error', 'Keine Marke angegeben.');
        }

        $brand = BrandsBrand::findOrFail($brandId);
        
        // Policy-Berechtigung prüfen
        $this->authorize('update', $brand);
        
        // Prüfen, ob bereits eine Facebook Page existiert (nur eine erlaubt)
        if ($brand->facebookPages()->exists()) {
            return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                ->with('error', 'Es ist bereits eine Facebook Page mit dieser Marke verknüpft.');
        }
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                ->with('error', 'Kein Team ausgewählt.');
        }

        // Brand ID und Team ID in Session speichern für Callback
        session([
            'brands_oauth_brand_id' => $brand->id,
            'brands_oauth_team_id' => $team->id,
        ]);

        // OAuth-Flow starten - direkt über Socialite
        $state = \Illuminate\Support\Str::random(32);
        session(['meta_oauth_state' => $state]);
        
        // Callback-Route generieren
        $callbackRoute = route('brands.facebook-pages.oauth.callback');
        
        // Redirect Domain aus Brands Config verwenden
        $redirectDomain = config('brands.meta.redirect_domain');
        if ($redirectDomain) {
            // Wenn redirect_domain gesetzt ist, diese verwenden
            // Prüfen ob callbackRoute bereits eine absolute URL ist
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
        
        Log::info('Brands OAuth redirect start', [
            'brand_id' => $brandId,
            'redirect_uri' => $redirectUri,
            'redirect_domain' => $redirectDomain,
        ]);
        
        try {
            // Meta OAuth Credentials aus Brands Config
            $clientId = config('brands.meta.client_id');
            $clientSecret = config('brands.meta.client_secret');
            
            if (!$clientId || !$clientSecret) {
                Log::error('Brands OAuth: Missing credentials', [
                    'has_client_id' => !empty($clientId),
                    'has_client_secret' => !empty($clientSecret),
                ]);
                return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                    ->with('error', 'Meta OAuth ist nicht konfiguriert. Bitte konfiguriere META_CLIENT_ID und META_CLIENT_SECRET in der .env Datei.');
            }
            
            // API Version für OAuth URL
            $apiVersion = config('brands.meta.api_version', 'v21.0');
            
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
            
            $redirectUrl = $provider
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
            if (strpos($redirectUrl, '/v') !== false && strpos($redirectUrl, $apiVersion) === false) {
                // Ersetze die Version in der URL
                $redirectUrl = preg_replace('/\/v\d+\.\d+\//', '/' . $apiVersion . '/', $redirectUrl);
            }
            
            Log::info('Brands OAuth redirect URL generated', [
                'redirect_url' => $redirectUrl,
                'redirect_url_length' => strlen($redirectUrl),
            ]);
            
            if (empty($redirectUrl)) {
                Log::error('Brands OAuth: Empty redirect URL');
                return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                    ->with('error', 'Fehler: OAuth Redirect-URL konnte nicht generiert werden.');
            }
            
            // Externer Redirect zu Facebook
            return redirect()->away($redirectUrl);
            
        } catch (\Exception $e) {
            Log::error('Brands OAuth redirect error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                ->with('error', 'Fehler beim Starten des OAuth-Flows: ' . $e->getMessage());
        }
    }

    /**
     * OAuth Callback für Facebook Page Verknüpfung
     */
    public function callback(Request $request)
    {
        // Brand ID und Team ID aus Session holen
        $brandId = session('brands_oauth_brand_id');
        $teamId = session('brands_oauth_team_id');

        if (!$brandId || !$teamId) {
            Log::error('Brands OAuth callback: Missing brand_id or team_id in session');
            return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId ?? 1])
                ->with('error', 'OAuth-Fehler: Kontext verloren. Bitte versuche es erneut.');
        }

        // State verifizieren
        $requestState = $request->query('state');
        $sessionState = session('meta_oauth_state');
        if ($requestState && (!$sessionState || $sessionState !== $requestState)) {
            Log::error('Brands OAuth state mismatch');
            return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                ->with('error', 'Ungültiger OAuth-State. Bitte versuche es erneut.');
        }
        session()->forget('meta_oauth_state');

        $code = $request->query('code');
        if (!$code) {
            $error = $request->query('error');
            return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                ->with('error', $error ?? 'OAuth-Fehler: Kein Code erhalten.');
        }

        try {
            // Access Token holen - direkt über Socialite, um auch Refresh Token zu bekommen
            $refreshToken = null;
            $expiresIn = null;
            $scopes = [];
            
            try {
                $user = \Laravel\Socialite\Facades\Socialite::driver('meta')->stateless()->user();
                $accessToken = $user->token;
                $expiresIn = $user->expiresIn ?? null;
                
                // Refresh Token extrahieren
                if (isset($user->refreshToken)) {
                    $refreshToken = $user->refreshToken;
                } elseif (isset($user->refresh_token)) {
                    $refreshToken = $user->refresh_token;
                } elseif (method_exists($user, 'getRefreshToken')) {
                    $refreshToken = $user->getRefreshToken();
                } elseif (method_exists($user, 'accessTokenResponse')) {
                    $tokenResponse = $user->accessTokenResponse;
                    $refreshToken = $tokenResponse['refresh_token'] ?? null;
                }
                
                // Scopes extrahieren
                if (method_exists($user, 'getScopes')) {
                    $scopes = $user->getScopes();
                } elseif (isset($user->scopes)) {
                    $scopes = $user->scopes;
                }
            } catch (\Exception $e) {
                Log::error('Failed to get user from Socialite', [
                    'error' => $e->getMessage(),
                ]);
                throw new \Exception('Fehler beim Abrufen des Access Tokens: ' . $e->getMessage(), 0, $e);
            }

            // Brand laden
            $brand = BrandsBrand::findOrFail($brandId);
            
            // Prüfen, ob bereits eine Facebook Page existiert
            if ($brand->facebookPages()->exists()) {
                session()->forget(['brands_oauth_brand_id', 'brands_oauth_team_id']);
                return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                    ->with('error', 'Es ist bereits eine Facebook Page mit dieser Marke verknüpft.');
            }

            $user = Auth::user();
            $team = $user->currentTeam;

            if (!$team || $team->id !== $teamId) {
                session()->forget(['brands_oauth_brand_id', 'brands_oauth_team_id']);
                return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                    ->with('error', 'Team-Kontext stimmt nicht überein.');
            }

            // Business Accounts holen über Graph API
            $apiVersion = config('brands.meta.api_version', 'v21.0');
            $businessResponse = Http::get("https://graph.facebook.com/{$apiVersion}/me/businesses", [
                'access_token' => $accessToken,
            ]);
            
            $businessAccounts = [];
            if ($businessResponse->successful()) {
                $businessData = $businessResponse->json();
                $businessAccounts = $businessData['data'] ?? [];
            }
            
            if (empty($businessAccounts)) {
                session()->forget(['brands_oauth_brand_id', 'brands_oauth_team_id']);
                return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                    ->with('error', 'Keine Business Accounts gefunden. Bitte stelle sicher, dass dein Meta-Account Zugriff auf Business Accounts hat.');
            }

            // Erste Business Account verwenden (später könnte man eine Auswahl-Seite einbauen)
            $businessId = $businessAccounts[0]['id'] ?? null;
            
            if (!$businessId) {
                session()->forget(['brands_oauth_brand_id', 'brands_oauth_team_id']);
                return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                    ->with('error', 'Keine gültige Business Account ID gefunden.');
            }

            // Facebook Pages holen über Graph API
            $pagesResponse = Http::get("https://graph.facebook.com/{$apiVersion}/{$businessId}/owned_pages", [
                'access_token' => $accessToken,
            ]);
            
            $pages = [];
            if ($pagesResponse->successful()) {
                $pagesData = $pagesResponse->json();
                $pages = $pagesData['data'] ?? [];
            }
            
            if (empty($pages)) {
                session()->forget(['brands_oauth_brand_id', 'brands_oauth_team_id']);
                return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                    ->with('error', 'Keine Facebook Pages gefunden.');
            }

            // Erste Facebook Page verwenden (nur eine erlaubt)
            $page = $pages[0];
            $pageId = $page['id'] ?? null;
            $pageName = $page['name'] ?? 'Facebook Page';
            $pageAccessToken = $page['access_token'] ?? $accessToken;

            // Facebook Page erstellen
            $facebookPage = \Platform\Brands\Models\BrandsFacebookPage::create([
                'external_id' => $pageId,
                'name' => $pageName,
                'description' => null,
                'access_token' => $pageAccessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
                'token_type' => 'Bearer',
                'scopes' => $scopes,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'brand_id' => $brand->id,
            ]);

            // Instagram Accounts holen über Graph API
            $instagramResponse = Http::get("https://graph.facebook.com/{$apiVersion}/{$businessId}/owned_instagram_accounts", [
                'access_token' => $accessToken,
            ]);
            
            $instagramAccounts = [];
            if ($instagramResponse->successful()) {
                $instagramData = $instagramResponse->json();
                $instagramAccounts = $instagramData['data'] ?? [];
            }
            
            // Automatisch Instagram Account anlegen, wenn vorhanden
            if (!empty($instagramAccounts)) {
                $instagramAccount = $instagramAccounts[0];
                $instagramId = $instagramAccount['id'] ?? null;
                $instagramUsername = $instagramAccount['username'] ?? 'instagram_account';

                if ($instagramId) {
                    \Platform\Brands\Models\BrandsInstagramAccount::create([
                        'external_id' => $instagramId,
                        'username' => $instagramUsername,
                        'description' => null,
                        'access_token' => $pageAccessToken, // Instagram nutzt Page Access Token
                        'refresh_token' => $refreshToken,
                        'expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
                        'token_type' => 'Bearer',
                        'scopes' => $scopes,
                        'user_id' => $user->id,
                        'team_id' => $team->id,
                        'brand_id' => $brand->id,
                        'facebook_page_id' => $facebookPage->id,
                    ]);
                }
            }

            // Session aufräumen
            session()->forget(['brands_oauth_brand_id', 'brands_oauth_team_id']);

            return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                ->with('success', 'Facebook Page wurde erfolgreich verknüpft. Instagram Account wurde automatisch angelegt, falls vorhanden.');

        } catch (\Exception $e) {
            Log::error('Brands OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->forget(['brands_oauth_brand_id', 'brands_oauth_team_id']);

            return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId ?? 1])
                ->with('error', 'Fehler beim Verknüpfen der Facebook Page: ' . $e->getMessage());
        }
    }
}
