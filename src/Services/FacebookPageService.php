<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsBrand;
use Platform\Integrations\Models\IntegrationsFacebookPage;
use Platform\Brands\Models\BrandsFacebookPost;
use Platform\Integrations\Services\IntegrationsFacebookPageService as CoreFacebookPageService;
use Platform\Brands\Services\BrandsMediaDownloadService;
use Illuminate\Support\Facades\Log;

/**
 * Service für Facebook Pages Management (Brands-spezifische Wrapper)
 */
class FacebookPageService
{
    protected CoreFacebookPageService $coreService;
    protected BrandsMediaDownloadService $mediaDownloadService;

    public function __construct(CoreFacebookPageService $coreService, BrandsMediaDownloadService $mediaDownloadService)
    {
        $this->coreService = $coreService;
        $this->mediaDownloadService = $mediaDownloadService;
    }

    /**
     * Ruft alle Facebook Pages für einen User/Team ab und speichert sie
     * Verknüpft sie dann mit der angegebenen Brand
     */
    public function syncFacebookPages(BrandsBrand $brand): array
    {
        $metaConnection = $brand->metaConnection();
        
        if (!$metaConnection) {
            throw new \Exception('Keine Meta-Connection für diese Marke gefunden. Bitte verknüpfe zuerst mit Meta.');
        }

        // Core-Service aufrufen
        $syncedPages = $this->coreService->syncFacebookPagesForUser($metaConnection);

        // TODO: Verknüpfung zur Brand implementieren, wenn benötigt
        foreach ($syncedPages as $facebookPage) {
            Log::info('Facebook Page synced for user', [
                'page_id' => $facebookPage->id,
                'user_id' => $metaConnection->owner_user_id,
            ]);
        }

        return $syncedPages;
    }

    /**
     * Ruft alle Facebook Posts für eine Facebook Page ab und speichert sie
     */
    public function syncFacebookPosts(IntegrationsFacebookPage $facebookPage, int $limit = 100): array
    {
        // Posts vom Core-Service abrufen
        $postsData = $this->coreService->fetchFacebookPosts($facebookPage, $limit);
        
        $userId = $facebookPage->user_id;
        $allPosts = [];
        $retrievedPostIds = [];

        foreach ($postsData as $postData) {
            // Post erstellen oder aktualisieren
            $post = BrandsFacebookPost::updateOrCreate(
                [
                    'external_id' => $postData['external_id'],
                    'facebook_page_id' => $facebookPage->id,
                ],
                [
                    'message' => $postData['message'],
                    'story' => $postData['story'],
                    'type' => $postData['type'],
                    'media_url' => $postData['media_url'],
                    'permalink_url' => $postData['permalink_url'],
                    'published_at' => $postData['published_at'],
                    'user_id' => $userId,
                ]
            );

            // Bild/Video herunterladen und speichern, falls vorhanden
            if ($postData['media_url']) {
                $this->downloadPostMedia($post, $postData['media_url']);
            }

            $allPosts[] = $post;
            $retrievedPostIds[] = $postData['external_id'];

            Log::info('Facebook Post synced', [
                'post_id' => $post->id,
                'external_id' => $postData['external_id'],
                'facebook_page_id' => $facebookPage->id,
            ]);
        }

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
