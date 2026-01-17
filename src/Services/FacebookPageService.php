<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\FacebookPage;
use Platform\Brands\Models\BrandsFacebookPost;
use Platform\Brands\Models\MetaToken;
use Platform\Brands\Services\BrandsMediaDownloadService;
use Platform\Core\Models\ContextFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service für Facebook Pages Management
 */
class FacebookPageService
{
    protected MetaTokenService $tokenService;
    protected BrandsMediaDownloadService $mediaDownloadService;

    public function __construct(MetaTokenService $tokenService, BrandsMediaDownloadService $mediaDownloadService)
    {
        $this->tokenService = $tokenService;
        $this->mediaDownloadService = $mediaDownloadService;
    }

    /**
     * Ruft alle Facebook Pages für einen User/Team ab und speichert sie
     * Verknüpft sie dann mit der angegebenen Brand
     */
    public function syncFacebookPages(BrandsBrand $brand): array
    {
        $metaToken = $brand->metaToken;
        
        if (!$metaToken) {
            throw new \Exception('Kein Meta-Token für diesen User/Team gefunden. Bitte verknüpfe zuerst mit Meta.');
        }

        $accessToken = $this->tokenService->getValidAccessToken($metaToken);
        
        if (!$accessToken) {
            throw new \Exception('Access Token konnte nicht abgerufen werden.');
        }

        $apiVersion = config('brands.meta.api_version', 'v21.0');
        // User-ID vom MetaToken nehmen (Token gehört User)
        $userId = $metaToken->user_id;

        // Business Accounts holen
        $businessResponse = Http::get("https://graph.facebook.com/{$apiVersion}/me/businesses", [
            'access_token' => $accessToken,
        ]);

        if ($businessResponse->failed()) {
            $error = $businessResponse->json()['error'] ?? [];
            throw new \Exception('Fehler beim Abrufen der Business Accounts: ' . ($error['message'] ?? 'Unbekannter Fehler'));
        }

        $businessData = $businessResponse->json();
        $businessAccounts = $businessData['data'] ?? [];

        if (empty($businessAccounts)) {
            Log::warning('No business accounts found', ['brand_id' => $brand->id]);
            return [];
        }

        $syncedPages = [];

        // Für jede Business Account die Pages holen
        foreach ($businessAccounts as $businessAccount) {
            $businessId = $businessAccount['id'];
            
            $pagesResponse = Http::get("https://graph.facebook.com/{$apiVersion}/{$businessId}/owned_pages", [
                'access_token' => $accessToken,
            ]);

            if ($pagesResponse->failed()) {
                Log::error('Failed to fetch pages for business', [
                    'business_id' => $businessId,
                    'error' => $pagesResponse->json()['error'] ?? [],
                ]);
                continue;
            }

            $pagesData = $pagesResponse->json();
            $pages = $pagesData['data'] ?? [];

            foreach ($pages as $pageData) {
                $pageId = $pageData['id'];
                $pageName = $pageData['name'] ?? 'Facebook Page';
                $pageAccessToken = $pageData['access_token'] ?? $accessToken;

                // Page auf User-Ebene erstellen oder aktualisieren (ohne team_id)
                $facebookPage = FacebookPage::updateOrCreate(
                    [
                        'external_id' => $pageId,
                        'user_id' => $userId,
                    ],
                    [
                        'name' => $pageName,
                        'description' => $pageData['about'] ?? null,
                        'access_token' => $pageAccessToken,
                        'refresh_token' => $metaToken->refresh_token,
                        'expires_at' => $metaToken->expires_at,
                        'token_type' => 'Bearer',
                        'scopes' => $metaToken->scopes,
                    ]
                );

                // Verknüpfung zur Brand über core_service_assets (falls noch nicht verknüpft)
                $serviceAsset = \Platform\Core\Models\CoreServiceAsset::where('service_type', BrandsBrand::class)
                    ->where('service_id', $brand->id)
                    ->where('asset_type', FacebookPage::class)
                    ->where('asset_id', $facebookPage->id)
                    ->first();
                
                if (!$serviceAsset) {
                    \Platform\Core\Models\CoreServiceAsset::create([
                        'service_type' => BrandsBrand::class,
                        'service_id' => $brand->id,
                        'asset_type' => FacebookPage::class,
                        'asset_id' => $facebookPage->id,
                    ]);
                }

                $syncedPages[] = $facebookPage;

                Log::info('Facebook Page synced', [
                    'page_id' => $facebookPage->id,
                    'external_id' => $pageId,
                    'brand_id' => $brand->id,
                    'user_id' => $userId,
                ]);
            }
        }

        return $syncedPages;
    }

    /**
     * Ruft alle Facebook Posts für eine Facebook Page ab und speichert sie
     */
    public function syncFacebookPosts(FacebookPage $facebookPage, int $limit = 100): array
    {
        $accessToken = $facebookPage->access_token;
        
        if (!$accessToken) {
            throw new \Exception('Kein Access Token für diese Facebook Page gefunden.');
        }

        $apiVersion = config('brands.meta.api_version', 'v21.0');
        // User-ID direkt von der Facebook Page nehmen (für Commands)
        $userId = $facebookPage->user_id;

        $params = [
            'fields' => 'id,message,story,created_time,permalink_url,attachments,type,status_type',
            'access_token' => $accessToken,
            'limit' => $limit,
        ];

        $url = "https://graph.facebook.com/{$apiVersion}/{$facebookPage->external_id}/posts";
        $allPosts = [];
        $retrievedPostIds = [];

        do {
            $response = Http::get($url, $params);

            if ($response->failed()) {
                $error = $response->json()['error'] ?? [];
                Log::error('Failed to fetch Facebook posts', [
                    'facebook_page_id' => $facebookPage->id,
                    'error' => $error,
                ]);
                break;
            }

            $data = $response->json();

            if (!isset($data['data']) || empty($data['data'])) {
                break;
            }

            foreach ($data['data'] as $postData) {
                $postId = $postData['id'];
                $createdTime = isset($postData['created_time']) 
                    ? Carbon::parse($postData['created_time'])->format('Y-m-d H:i:s')
                    : null;

                // Media URL aus Attachments extrahieren
                $mediaUrl = null;
                if (isset($postData['attachments']['data'][0]['media'])) {
                    $mediaUrl = $postData['attachments']['data'][0]['media']['image']['src'] ?? null;
                }

                // Post erstellen oder aktualisieren
                $post = BrandsFacebookPost::updateOrCreate(
                    [
                        'external_id' => $postId,
                        'facebook_page_id' => $facebookPage->id,
                    ],
                    [
                        'message' => $postData['message'] ?? null,
                        'story' => $postData['story'] ?? null,
                        'type' => $postData['type'] ?? $postData['status_type'] ?? null,
                        'media_url' => $mediaUrl,
                        'permalink_url' => $postData['permalink_url'] ?? null,
                        'published_at' => $createdTime,
                        'user_id' => $userId,
                    ]
                );

                // Bild/Video herunterladen und speichern, falls vorhanden
                if ($mediaUrl) {
                    $this->downloadPostMedia($post, $mediaUrl);
                }

                $allPosts[] = $post;
                $retrievedPostIds[] = $postId;

                Log::info('Facebook Post synced', [
                    'post_id' => $post->id,
                    'external_id' => $postId,
                    'facebook_page_id' => $facebookPage->id,
                ]);
            }

            $url = $data['paging']['next'] ?? null;
        } while ($url);

        // Posts löschen, die nicht mehr existieren
        $existingPostIds = BrandsFacebookPost::where('facebook_page_id', $facebookPage->id)
            ->pluck('external_id')
            ->toArray();
        
        $deletedPostIds = array_diff($existingPostIds, $retrievedPostIds);
        
        if (!empty($deletedPostIds)) {
            BrandsFacebookPost::where('facebook_page_id', $facebookPage->id)
                ->whereIn('external_id', $deletedPostIds)
                ->delete();
            
            Log::info('Facebook Posts deleted', [
                'facebook_page_id' => $facebookPage->id,
                'deleted_count' => count($deletedPostIds),
            ]);
        }

        return $allPosts;
    }

    /**
     * Lädt Bild/Video für einen Facebook Post herunter und speichert es als ContextFile
     */
    protected function downloadPostMedia(BrandsFacebookPost $post, string $mediaUrl): void
    {
        $contextType = BrandsFacebookPost::class;
        $contextId = $post->id;
        
        $this->mediaDownloadService->downloadAndStore(
            $mediaUrl,
            $contextType,
            $contextId,
            [
                'facebook_post_id' => $post->id,
                'post_type' => $post->type,
                'role' => 'primary',
                'generate_variants' => true,
            ]
        );
    }
}
