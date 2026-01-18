<?php

namespace Platform\Brands\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Platform\Integrations\Models\IntegrationsMetaToken;
use Laravel\Socialite\Facades\Socialite;

class FacebookPageOAuthController extends Controller
{
    use AuthorizesRequests;
    /**
     * Startet den OAuth-Flow für Meta-Verbindung (User-Ebene)
     */
    public function redirect(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('dashboard')
                ->with('error', 'Nicht angemeldet.');
        }

        // OAuth-Flow starten - direkt über Socialite
        $state = \Illuminate\Support\Str::random(32);
        session(['meta_oauth_state' => $state]);
        
        // Callback-Route generieren (nur Pfad, keine absolute URL)
        $callbackPath = '/meta/oauth/callback';
        
        // Redirect Domain aus Brands Config verwenden
        $redirectDomain = config('brands.meta.redirect_domain');
        if ($redirectDomain) {
            // Domain + Pfad kombinieren
            $redirectUri = rtrim($redirectDomain, '/') . $callbackPath;
        } else {
            // Fallback: absolute URL aus route() generieren
            $redirectUri = url($callbackPath);
        }
        
        Log::info('Brands OAuth redirect start', [
            'user_id' => $user->id,
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
                return redirect()->back()
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
                return redirect()->back()
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
            
            return redirect()->back()
                ->with('error', 'Fehler beim Starten des OAuth-Flows: ' . $e->getMessage());
        }
    }

    /**
     * OAuth Callback für Meta-Verbindung (User-Ebene)
     */
    public function callback(Request $request)
    {
        Log::info('Brands OAuth callback: Start', [
            'query_params' => $request->query(),
        ]);
        
        $user = Auth::user();
        
        if (!$user) {
            Log::error('Brands OAuth callback: User not authenticated');
            return redirect()->route('dashboard')
                ->with('error', 'OAuth-Fehler: Nicht angemeldet.');
        }

        // State verifizieren
        $requestState = $request->query('state');
        $sessionState = session('meta_oauth_state');
        if ($requestState && (!$sessionState || $sessionState !== $requestState)) {
            Log::error('Brands OAuth state mismatch');
            return redirect()->back()
                ->with('error', 'Ungültiger OAuth-State. Bitte versuche es erneut.');
        }
        session()->forget('meta_oauth_state');

        $code = $request->query('code');
        if (!$code) {
            $error = $request->query('error');
            return redirect()->back()
                ->with('error', $error ?? 'OAuth-Fehler: Kein Code erhalten.');
        }

        try {
            // Access Token holen - direkt über Socialite mit FacebookProvider
            $refreshToken = null;
            $expiresIn = null;
            $scopes = [];
            $accessToken = null;
            
            try {
                // Callback-Route für Socialite (nur Pfad, keine absolute URL)
                $callbackPath = '/meta/oauth/callback';
                
                // Redirect Domain aus Brands Config verwenden
                $redirectDomain = config('brands.meta.redirect_domain');
                if ($redirectDomain) {
                    // Domain + Pfad kombinieren
                    $redirectUri = rtrim($redirectDomain, '/') . $callbackPath;
                } else {
                    // Fallback: absolute URL generieren
                    $redirectUri = url($callbackPath);
                }
                
                // Meta OAuth Credentials aus Brands Config
                $clientId = config('brands.meta.client_id');
                $clientSecret = config('brands.meta.client_secret');
                
                if (!$clientId || !$clientSecret) {
                    throw new \Exception('Meta OAuth Credentials nicht konfiguriert.');
                }
                
                // Socialite Provider mit gleichen Credentials wie im redirect()
                $provider = Socialite::buildProvider(
                    \Laravel\Socialite\Two\FacebookProvider::class,
                    [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'redirect' => $redirectUri,
                    ]
                );
                
                $user = $provider->stateless()->user();
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
                
                Log::info('Brands OAuth callback: Token erhalten', [
                    'has_access_token' => !empty($accessToken),
                    'has_refresh_token' => !empty($refreshToken),
                    'expires_in' => $expiresIn,
                    'scopes_count' => count($scopes),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to get user from Socialite', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw new \Exception('Fehler beim Abrufen des Access Tokens: ' . $e->getMessage(), 0, $e);
            }
            
            if (!$accessToken) {
                throw new \Exception('Access Token konnte nicht abgerufen werden.');
            }

            // Token speichern auf User-Ebene (user-zentriert)
            Log::info('Brands OAuth callback: Speichere Token', [
                'user_id' => $user->id,
                'has_access_token' => !empty($accessToken),
                'has_refresh_token' => !empty($refreshToken),
                'expires_in' => $expiresIn,
            ]);

            $metaToken = IntegrationsMetaToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
                    'token_type' => 'Bearer',
                    'scopes' => $scopes,
                ]
            );

            Log::info('Brands OAuth callback: Token gespeichert', [
                'meta_token_id' => $metaToken->id,
                'uuid' => $metaToken->uuid,
            ]);

            return redirect()->back()
                ->with('success', 'Meta OAuth Token wurde erfolgreich gespeichert. Du kannst nun Facebook Pages und Instagram Accounts abrufen.');

        } catch (\Exception $e) {
            Log::error('Brands OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Fehler beim Verknüpfen der Meta-Verbindung: ' . $e->getMessage());
        }
    }
}
