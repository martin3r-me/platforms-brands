<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsInstagramAccount;
use Platform\Brands\Models\BrandsInstagramMedia;
use Platform\Brands\Services\BrandsMediaDownloadService;
use Platform\Core\Models\ContextFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service für Instagram Media Management
 */
class InstagramMediaService
{
    protected MetaTokenService $tokenService;
    protected BrandsMediaDownloadService $mediaDownloadService;

    public function __construct(MetaTokenService $tokenService, BrandsMediaDownloadService $mediaDownloadService)
    {
        $this->tokenService = $tokenService;
        $this->mediaDownloadService = $mediaDownloadService;
    }

    /**
     * Ruft alle Instagram Media für einen Account ab
     * 
     * @return array Array mit Media-Daten
     */
    public function fetchMedia(BrandsInstagramAccount $account, int $limit = 1000): array
    {
        $accessToken = $account->access_token;
        
        if (!$accessToken) {
            throw new \Exception('Kein Access Token für diesen Instagram Account gefunden.');
        }

        $apiVersion = config('brands.meta.api_version', 'v21.0');
        $allMedia = [];

        // Reguläre Media abrufen
        $params = [
            'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,like_count,comments_count,children{media_type,media_url}',
            'access_token' => $accessToken,
            'limit' => $limit,
        ];

        $url = "https://graph.facebook.com/{$apiVersion}/{$account->external_id}/media";

        do {
            $response = Http::get($url, $params);

            if ($response->failed()) {
                $error = $response->json()['error'] ?? [];
                Log::error('Failed to fetch Instagram media', [
                    'account_id' => $account->id,
                    'error' => $error,
                ]);
                break;
            }

            $data = $response->json();

            if (isset($data['data'])) {
                foreach ($data['data'] as $mediaData) {
                    $allMedia[] = $this->normalizeMediaData($mediaData, false);
                }
            }

            $url = $data['paging']['next'] ?? null;
        } while ($url);

        // Stories abrufen
        $storiesParams = [
            'fields' => 'id,media_type,media_url,permalink,timestamp',
            'access_token' => $accessToken,
        ];

        $storiesUrl = "https://graph.facebook.com/{$apiVersion}/{$account->external_id}/stories";
        $storiesResponse = Http::get($storiesUrl, $storiesParams);

        if ($storiesResponse->successful()) {
            $storiesData = $storiesResponse->json();
            
            if (isset($storiesData['data'])) {
                foreach ($storiesData['data'] as $storyData) {
                    $allMedia[] = $this->normalizeMediaData($storyData, true);
                }
            }
        }

        Log::info('Instagram media fetched', [
            'account_id' => $account->id,
            'count' => count($allMedia),
        ]);

        return $allMedia;
    }

    /**
     * Speichert Instagram Media in der Datenbank und lädt Bilder herunter
     */
    public function syncMedia(BrandsInstagramAccount $account, int $limit = 1000): array
    {
        $mediaData = $this->fetchMedia($account, $limit);
        // Team-ID und User-ID direkt vom Instagram Account nehmen (für Commands)
        $teamId = $account->team_id;
        $userId = $account->user_id;
        $syncedMedia = [];

        foreach ($mediaData as $data) {
            // Media in DB speichern
            $instagramMedia = BrandsInstagramMedia::updateOrCreate(
                [
                    'external_id' => $data['media_id'],
                    'instagram_account_id' => $account->id,
                ],
                [
                    'caption' => $data['caption'],
                    'media_type' => $data['media_type'],
                    'media_url' => $data['media_url'],
                    'permalink' => $data['permalink'],
                    'thumbnail_url' => $data['thumbnail_url'],
                    'timestamp' => $data['timestamp'],
                    'like_count' => $data['like_count'],
                    'comments_count' => $data['comments_count'],
                    'is_story' => $data['is_story'],
                    'insights_available' => true,
                    'user_id' => $userId,
                    'team_id' => $teamId,
                ]
            );

            // Bilder/Videos herunterladen und speichern
            try {
                $this->downloadMediaFiles($instagramMedia, $data);
            } catch (\Exception $e) {
                Log::error('Error downloading media files for Instagram Media', [
                    'instagram_media_id' => $instagramMedia->id,
                    'external_id' => $data['media_id'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $syncedMedia[] = $instagramMedia;
        }

        return $syncedMedia;
    }

    /**
     * Lädt Bilder/Videos für ein Media-Item herunter und speichert sie als ContextFile
     */
    protected function downloadMediaFiles(BrandsInstagramMedia $instagramMedia, array $mediaData): void
    {
        $contextType = BrandsInstagramMedia::class;
        $contextId = $instagramMedia->id;
        
        Log::info('Downloading media files for Instagram Media', [
            'instagram_media_id' => $instagramMedia->id,
            'external_id' => $mediaData['media_id'],
            'media_type' => $mediaData['media_type'],
            'has_media_url' => !empty($mediaData['media_url']),
            'has_thumbnail_url' => !empty($mediaData['thumbnail_url']),
            'has_children' => !empty($mediaData['children']),
        ]);
        
        // Hauptbild/Video herunterladen
        if (!empty($mediaData['media_url'])) {
            $result = $this->mediaDownloadService->downloadAndStore(
                $mediaData['media_url'],
                $contextType,
                $contextId,
                [
                    'instagram_media_id' => $instagramMedia->id,
                    'media_type' => $mediaData['media_type'],
                    'role' => 'primary',
                    'is_primary' => true,
                    'generate_variants' => false, // Instagram-Bilder sind bereits optimiert
                ]
            );
            
            if ($result) {
                Log::info('Primary media downloaded successfully', [
                    'instagram_media_id' => $instagramMedia->id,
                    'context_file_id' => $result->id,
                ]);
            } else {
                Log::warning('Failed to download primary media', [
                    'instagram_media_id' => $instagramMedia->id,
                    'media_url' => $mediaData['media_url'],
                ]);
            }
        } else {
            Log::warning('No media_url found for Instagram Media', [
                'instagram_media_id' => $instagramMedia->id,
                'media_type' => $mediaData['media_type'],
            ]);
        }

        // Thumbnail herunterladen (falls vorhanden und unterschiedlich)
        if (!empty($mediaData['thumbnail_url']) && $mediaData['thumbnail_url'] !== $mediaData['media_url']) {
            $result = $this->mediaDownloadService->downloadAndStore(
                $mediaData['thumbnail_url'],
                $contextType,
                $contextId,
                [
                    'instagram_media_id' => $instagramMedia->id,
                    'media_type' => 'thumbnail',
                    'role' => 'thumbnail',
                    'generate_variants' => false, // Instagram-Thumbnails sind bereits optimiert
                ]
            );
            
            if ($result) {
                Log::info('Thumbnail downloaded successfully', [
                    'instagram_media_id' => $instagramMedia->id,
                    'context_file_id' => $result->id,
                ]);
            }
        }

        // Children (Carousel) herunterladen
        if (!empty($mediaData['children'])) {
            Log::info('Downloading carousel children', [
                'instagram_media_id' => $instagramMedia->id,
                'children_count' => count($mediaData['children']),
            ]);
            
            foreach ($mediaData['children'] as $index => $child) {
                if (!empty($child['media_url'])) {
                    $result = $this->mediaDownloadService->downloadAndStore(
                        $child['media_url'],
                        $contextType,
                        $contextId,
                        [
                            'instagram_media_id' => $instagramMedia->id,
                            'media_type' => $child['media_type'] ?? 'image',
                            'role' => 'carousel',
                            'is_carousel_item' => true,
                            'carousel_index' => $index,
                            'generate_variants' => false, // Instagram-Carousel-Bilder sind bereits optimiert
                        ]
                    );
                    
                    if ($result) {
                        Log::info('Carousel item downloaded successfully', [
                            'instagram_media_id' => $instagramMedia->id,
                            'carousel_index' => $index,
                            'context_file_id' => $result->id,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Normalisiert Media-Daten für einheitliche Struktur
     */
    protected function normalizeMediaData(array $mediaData, bool $isStory = false): array
    {
        return [
            'media_id' => $mediaData['id'],
            'caption' => $mediaData['caption'] ?? null,
            'media_type' => $mediaData['media_type'],
            'media_url' => $mediaData['media_url'] ?? null,
            'permalink' => $mediaData['permalink'] ?? null,
            'thumbnail_url' => $mediaData['thumbnail_url'] ?? null,
            'timestamp' => isset($mediaData['timestamp']) 
                ? Carbon::parse($mediaData['timestamp'])->format('Y-m-d H:i:s')
                : null,
            'like_count' => $mediaData['like_count'] ?? 0,
            'comments_count' => $mediaData['comments_count'] ?? 0,
            'is_story' => $isStory,
            'children' => $mediaData['children']['data'] ?? [],
        ];
    }
}
