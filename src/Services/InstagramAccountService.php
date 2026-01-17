<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsFacebookPage;
use Platform\Brands\Models\BrandsInstagramAccount;
use Platform\Brands\Models\BrandsMetaToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Service für Instagram Accounts Management
 */
class InstagramAccountService
{
    protected MetaTokenService $tokenService;

    public function __construct(MetaTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Ruft Instagram Accounts für eine Brand ab und speichert sie
     */
    public function syncInstagramAccounts(BrandsBrand $brand): array
    {
        $metaToken = $brand->metaToken;
        
        if (!$metaToken) {
            throw new \Exception('Kein Meta-Token für diese Marke gefunden. Bitte verknüpfe zuerst die Marke mit Meta.');
        }

        $accessToken = $this->tokenService->getValidAccessToken($metaToken);
        
        if (!$accessToken) {
            throw new \Exception('Access Token konnte nicht abgerufen werden.');
        }

        $apiVersion = config('brands.meta.api_version', 'v21.0');
        $user = Auth::user();
        $team = $user->currentTeam;

        // Instagram Accounts über Facebook Pages holen
        $facebookPages = $brand->facebookPages;
        $syncedAccounts = [];

        foreach ($facebookPages as $facebookPage) {
            $pageAccessToken = $facebookPage->access_token ?? $accessToken;

            // Versuche Instagram Account direkt über die Facebook Page zu holen
            $instagramResponse = Http::get("https://graph.facebook.com/{$apiVersion}/{$facebookPage->external_id}", [
                'fields' => 'instagram_business_account',
                'access_token' => $pageAccessToken,
            ]);

            if ($instagramResponse->successful()) {
                $instagramData = $instagramResponse->json();
                
                if (isset($instagramData['instagram_business_account'])) {
                    $accountData = $instagramData['instagram_business_account'];
                    $instagramId = $accountData['id'] ?? null;

                    if ($instagramId) {
                        // Instagram Username separat abrufen
                        $username = $this->fetchInstagramUsername($instagramId, $pageAccessToken, $apiVersion);
                        
                        $instagramAccount = BrandsInstagramAccount::updateOrCreate(
                            [
                                'external_id' => $instagramId,
                                'brand_id' => $brand->id,
                            ],
                            [
                                'username' => $username,
                                'description' => null,
                                'access_token' => $pageAccessToken,
                                'refresh_token' => $metaToken->refresh_token,
                                'expires_at' => $metaToken->expires_at,
                                'token_type' => 'Bearer',
                                'scopes' => $metaToken->scopes,
                                'user_id' => $user->id,
                                'team_id' => $team->id,
                                'facebook_page_id' => $facebookPage->id,
                            ]
                        );

                        $syncedAccounts[] = $instagramAccount;

                        Log::info('Instagram Account synced via Facebook Page', [
                            'instagram_account_id' => $instagramAccount->id,
                            'external_id' => $instagramId,
                            'facebook_page_id' => $facebookPage->id,
                            'brand_id' => $brand->id,
                        ]);
                    }
                }
            }

            // Fallback: Versuche über Business Account
            if (empty($syncedAccounts)) {
                $businessResponse = Http::get("https://graph.facebook.com/{$apiVersion}/me/businesses", [
                    'access_token' => $accessToken,
                ]);

                if ($businessResponse->successful()) {
                    $businessData = $businessResponse->json();
                    $businessAccounts = $businessData['data'] ?? [];

                    foreach ($businessAccounts as $businessAccount) {
                        $businessId = $businessAccount['id'];
                        
                        $instagramResponse = Http::get("https://graph.facebook.com/{$apiVersion}/{$businessId}/owned_instagram_accounts", [
                            'access_token' => $accessToken,
                        ]);

                        if ($instagramResponse->successful()) {
                            $instagramData = $instagramResponse->json();
                            $instagramAccounts = $instagramData['data'] ?? [];

                            foreach ($instagramAccounts as $accountData) {
                                $instagramId = $accountData['id'] ?? null;

                                if ($instagramId) {
                                    $username = $this->fetchInstagramUsername($instagramId, $pageAccessToken, $apiVersion);
                                    
                                    $instagramAccount = BrandsInstagramAccount::updateOrCreate(
                                        [
                                            'external_id' => $instagramId,
                                            'brand_id' => $brand->id,
                                        ],
                                        [
                                            'username' => $username,
                                            'description' => null,
                                            'access_token' => $pageAccessToken,
                                            'refresh_token' => $metaToken->refresh_token,
                                            'expires_at' => $metaToken->expires_at,
                                            'token_type' => 'Bearer',
                                            'scopes' => $metaToken->scopes,
                                            'user_id' => $user->id,
                                            'team_id' => $team->id,
                                            'facebook_page_id' => $facebookPage->id,
                                        ]
                                    );

                                    $syncedAccounts[] = $instagramAccount;

                                    Log::info('Instagram Account synced via Business Account', [
                                        'instagram_account_id' => $instagramAccount->id,
                                        'external_id' => $instagramId,
                                        'brand_id' => $brand->id,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $syncedAccounts;
    }

    /**
     * Ruft den Instagram Username ab
     */
    protected function fetchInstagramUsername(string $instagramId, string $accessToken, string $apiVersion): string
    {
        try {
            $response = Http::get("https://graph.facebook.com/{$apiVersion}/{$instagramId}", [
                'fields' => 'username',
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['username'] ?? 'instagram_account';
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Instagram username', [
                'instagram_id' => $instagramId,
                'error' => $e->getMessage(),
            ]);
        }

        return 'instagram_account';
    }
}
