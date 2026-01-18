<?php

namespace Platform\Brands\Services;

use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Platform\Integrations\Services\IntegrationsMetaTokenService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service für Instagram Hashtags Management
 */
class InstagramHashtagsService
{
    protected IntegrationsMetaTokenService $tokenService;

    public function __construct(IntegrationsMetaTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Extrahiert Hashtags aus einer Caption mit Anzahl
     * 
     * @return array Array mit Hashtag-Namen als Keys und Anzahl als Values
     */
    public function extractHashtags(string $caption): array
    {
        if (empty($caption)) {
            return [];
        }

        preg_match_all('/#(\w+)/', $caption, $matches);
        return array_count_values($matches[0]); // Zählt die Hashtag-Vorkommen
    }

    /**
     * Ruft die Instagram Hashtag ID für einen Hashtag ab
     */
    public function fetchHashtagId(string $hashtag, IntegrationsInstagramAccount $account): ?string
    {
        $apiVersion = config('integrations.oauth2.providers.meta.api_version', config('brands.meta.api_version', 'v21.0'));
        
        // Access Token vom Account oder vom User holen
        $accessToken = $account->access_token;
        if (!$accessToken) {
            $metaToken = \Platform\Integrations\Models\IntegrationsMetaToken::where('user_id', $account->user_id)
                ->first();
            if ($metaToken) {
                $accessToken = $this->tokenService->getValidAccessToken($metaToken);
            }
        }
        
        if (!$accessToken) {
            Log::error('Kein Access Token für Instagram Account gefunden', [
                'account_id' => $account->id,
            ]);
            return null;
        }

        try {
            $response = Http::get("https://graph.facebook.com/{$apiVersion}/ig_hashtag_search", [
                'user_id' => $account->external_id,
                'q' => ltrim($hashtag, '#'), // Entferne das # für die Suche
                'access_token' => $accessToken,
            ]);

            if ($response->failed()) {
                $error = $response->json()['error'] ?? [];
                $errorMessage = $error['message'] ?? 'Unknown error';
                
                // Resource Limit Error
                if (strpos($errorMessage, 'resource limits') !== false) {
                    Log::warning('Instagram Hashtag Search resource limit reached', [
                        'hashtag' => $hashtag,
                        'account_id' => $account->id,
                    ]);
                    return null;
                }

                Log::error('Failed to fetch Instagram hashtag ID', [
                    'hashtag' => $hashtag,
                    'account_id' => $account->id,
                    'error' => $error,
                ]);
                return null;
            }

            $data = $response->json();
            $hashtagId = $data['data'][0]['id'] ?? null;

            if ($hashtagId) {
                Log::info('Instagram Hashtag ID fetched', [
                    'hashtag' => $hashtag,
                    'hashtag_id' => $hashtagId,
                    'account_id' => $account->id,
                ]);
            }

            return $hashtagId;
        } catch (\Exception $e) {
            Log::error('Exception fetching Instagram Hashtag ID', [
                'hashtag' => $hashtag,
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verarbeitet Hashtags aus einer Media-Caption
     * 
     * @return array Array mit Hashtag-Daten: ['hashtag' => '#tag', 'count' => 2, 'instagram_hashtag_id' => '123']
     */
    public function processHashtags(string $caption, IntegrationsInstagramAccount $account, bool $fetchIds = true): array
    {
        $hashtags = $this->extractHashtags($caption);
        $processed = [];

        foreach ($hashtags as $hashtag => $count) {
            $data = [
                'hashtag' => $hashtag,
                'count' => $count,
                'instagram_hashtag_id' => null,
            ];

            if ($fetchIds) {
                $hashtagId = $this->fetchHashtagId($hashtag, $account);
                if ($hashtagId) {
                    $data['instagram_hashtag_id'] = $hashtagId;
                }
            }

            $processed[] = $data;
        }

        return $processed;
    }
}
