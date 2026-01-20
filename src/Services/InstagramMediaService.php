<?php

namespace Platform\Brands\Services;

use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Platform\Brands\Models\BrandsInstagramMedia;
use Platform\Brands\Services\BrandsMediaDownloadService;
use Platform\Core\Models\ContextFile;
use Platform\Core\Models\User;
use Platform\Integrations\Services\MetaIntegrationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service für Instagram Media Management
 */
class InstagramMediaService
{
    protected MetaIntegrationService $metaService;
    protected BrandsMediaDownloadService $mediaDownloadService;

    public function __construct(MetaIntegrationService $metaService, BrandsMediaDownloadService $mediaDownloadService)
    {
        $this->metaService = $metaService;
        $this->mediaDownloadService = $mediaDownloadService;
    }

    /**
     * Ruft alle Instagram Media für einen Account ab
     * 
     * @return array Array mit Media-Daten
     */
    public function fetchMedia(IntegrationsInstagramAccount $account, int $limit = 1000): array
    {
        // User holen und Meta Connection prüfen
        $user = User::find($account->user_id);
        if (!$user) {
            throw new \Exception('User nicht gefunden.');
        }
        
        $accessToken = $this->metaService->getValidAccessTokenForUser($user);
        
        if (!$accessToken) {
            throw new \Exception('Kein gültiger Meta Access Token für diesen User gefunden. Bitte zuerst Meta über OAuth verbinden.');
        }

        $apiVersion = config('integrations.oauth2.providers.meta.api_version', '21.0');
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
     * 
     * @param IntegrationsInstagramAccount $account
     * @param int $limit
     * @param \Illuminate\Console\Command|null $command Für Terminal-Ausgabe
     * @return array
     */
    public function syncMedia(IntegrationsInstagramAccount $account, int $limit = 1000, ?\Illuminate\Console\Command $command = null): array
    {
        $mediaData = $this->fetchMedia($account, $limit);
        // User-ID direkt vom Instagram Account nehmen (für Commands)
        $userId = $account->user_id;
        $syncedMedia = [];
        $retrievedMediaIds = [];
        
        $totalCount = count($mediaData);
        if ($command) {
            $command->info("     📥 {$totalCount} Media-Item(s) gefunden");
        }

        foreach ($mediaData as $index => $data) {
            $retrievedMediaIds[] = $data['media_id'];
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
                ]
            );

            // WICHTIG: Model refreshen, um sicherzustellen, dass alle Beziehungen geladen sind
            $instagramMedia->refresh();

            // Bilder/Videos herunterladen und speichern
            try {
                if ($command && ($index + 1) % 10 === 0) {
                    $command->line("     ⏳ Verarbeite Media " . ($index + 1) . "/{$totalCount}...");
                }
                
                $this->downloadMediaFiles($instagramMedia, $data, $command);
                
                if ($command && !empty($data['media_url'])) {
                    $command->line("     ✅ Media {$data['media_id']} heruntergeladen");
                }
            } catch (\Exception $e) {
                if ($command) {
                    $command->error("     ❌ Fehler beim Download von Media {$data['media_id']}: {$e->getMessage()}");
                }
                Log::error('Error downloading media files for Instagram Media', [
                    'instagram_media_id' => $instagramMedia->id,
                    'external_id' => $data['media_id'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $syncedMedia[] = $instagramMedia;
        }

        // Media löschen, die nicht mehr existieren
        $existingMediaIds = BrandsInstagramMedia::where('instagram_account_id', $account->id)
            ->pluck('external_id')
            ->toArray();
        
        $deletedMediaIds = array_diff($existingMediaIds, $retrievedMediaIds);
        
        if (!empty($deletedMediaIds)) {
            $deletedCount = BrandsInstagramMedia::where('instagram_account_id', $account->id)
                ->whereIn('external_id', $deletedMediaIds)
                ->delete();
            
            if ($command) {
                $command->info("     🗑️  {$deletedCount} gelöschte Media-Item(s) entfernt");
            }
            
            Log::info('Instagram Media deleted', [
                'instagram_account_id' => $account->id,
                'deleted_count' => $deletedCount,
                'deleted_ids' => $deletedMediaIds,
            ]);
        }

        return $syncedMedia;
    }

    /**
     * Lädt Bilder/Videos für ein Media-Item herunter und speichert sie als ContextFile
     */
    protected function downloadMediaFiles(BrandsInstagramMedia $instagramMedia, array $mediaData, ?\Illuminate\Console\Command $command = null): void
    {
        $contextType = BrandsInstagramMedia::class;
        $contextId = $instagramMedia->id;
        
        // Hauptbild/Video herunterladen
        if (!empty($mediaData['media_url'])) {
            // Prüfen, ob bereits ein ContextFile mit dieser Rolle existiert
            $existingContextFile = ContextFile::where('context_type', $contextType)
                ->where('context_id', $contextId)
                ->whereJsonContains('meta->role', 'primary')
                ->first();
            
            if ($existingContextFile) {
                if ($command) {
                    $command->line("       ⏭️  Media bereits heruntergeladen (ContextFile ID: {$existingContextFile->id})");
                }
            } else {
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
                    ],
                    $command
                );
                
                if ($result && $command) {
                    $command->line("       📁 ContextFile erstellt: ID {$result->id}");
                } elseif (!$result && $command) {
                    $command->warn("       ⚠️  Konnte ContextFile nicht erstellen");
                }
            }
        }

        // Thumbnail herunterladen (falls vorhanden und unterschiedlich)
        if (!empty($mediaData['thumbnail_url']) && $mediaData['thumbnail_url'] !== $mediaData['media_url']) {
            // Prüfen, ob bereits ein Thumbnail existiert
            $existingThumbnail = ContextFile::where('context_type', $contextType)
                ->where('context_id', $contextId)
                ->whereJsonContains('meta->role', 'thumbnail')
                ->first();
            
            if (!$existingThumbnail) {
                $this->mediaDownloadService->downloadAndStore(
                    $mediaData['thumbnail_url'],
                    $contextType,
                    $contextId,
                    [
                        'instagram_media_id' => $instagramMedia->id,
                        'media_type' => 'thumbnail',
                        'role' => 'thumbnail',
                        'generate_variants' => false, // Instagram-Thumbnails sind bereits optimiert
                    ],
                    $command
                );
            }
        }

        // Children (Carousel) herunterladen
        if (!empty($mediaData['children'])) {
            if ($command) {
                $command->line("       🎠 Carousel mit " . count($mediaData['children']) . " Items");
            }
            
            foreach ($mediaData['children'] as $index => $child) {
                if (!empty($child['media_url'])) {
                    // Prüfen, ob bereits ein Carousel-Item mit diesem Index existiert
                    $existingCarouselItem = ContextFile::where('context_type', $contextType)
                        ->where('context_id', $contextId)
                        ->whereJsonContains('meta->role', 'carousel')
                        ->whereJsonContains('meta->carousel_index', $index)
                        ->first();
                    
                    if (!$existingCarouselItem) {
                        $this->mediaDownloadService->downloadAndStore(
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
                            ],
                            $command
                        );
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
