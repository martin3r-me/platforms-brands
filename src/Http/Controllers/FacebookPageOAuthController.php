<?php

namespace Platform\Brands\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Platform\Brands\Models\BrandsBrand;
use Platform\MetaOAuth\Services\MetaOAuthService;
use Platform\MetaOAuth\Services\MetaGraphApiService;

class FacebookPageOAuthController extends Controller
{
    protected MetaOAuthService $metaOAuthService;
    protected MetaGraphApiService $graphApi;

    public function __construct(MetaOAuthService $metaOAuthService, MetaGraphApiService $graphApi)
    {
        $this->metaOAuthService = $metaOAuthService;
        $this->graphApi = $graphApi;
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
        if ($requestState && !$this->metaOAuthService->verifyState($requestState)) {
            Log::error('Brands OAuth state mismatch');
            return redirect()->route('brands.brands.show', ['brandsBrand' => $brandId])
                ->with('error', 'Ungültiger OAuth-State. Bitte versuche es erneut.');
        }

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

            // Business Accounts holen
            $businessAccounts = $this->metaOAuthService->getBusinessAccounts($accessToken);
            
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

            // Facebook Pages holen
            $pages = $this->metaOAuthService->getFacebookPages($accessToken, $businessId);
            
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

            // Instagram Accounts holen
            $instagramAccounts = $this->metaOAuthService->getInstagramAccounts($accessToken, $businessId);
            
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
