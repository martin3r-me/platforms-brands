<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsSocialCardContract;
use Platform\Integrations\Models\IntegrationsFacebookPage;
use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Platform\Integrations\Services\MetaIntegrationService;
use Platform\Core\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service für das Publishing von Social Card Contracts über die Meta Graph API.
 *
 * Unterstützt:
 * - Facebook Page Posts (Text + Bild + Link)
 * - Instagram Container Publishing (Photo + Carousel)
 */
class MetaPublishingService
{
    protected MetaIntegrationService $metaService;

    public function __construct(MetaIntegrationService $metaService)
    {
        $this->metaService = $metaService;
    }

    /**
     * Published einen Contract auf Facebook.
     *
     * @return array{success: bool, external_post_id: ?string, error: ?string}
     */
    public function publishToFacebook(BrandsSocialCardContract $contract, User $user, int $teamId): array
    {
        try {
            $accessToken = $this->metaService->getValidAccessTokenForUser($user);
            if (!$accessToken) {
                return [
                    'success' => false,
                    'external_post_id' => null,
                    'error' => 'Kein gültiger Meta Access Token gefunden. Bitte Meta über OAuth verbinden.',
                ];
            }

            // Facebook Page für das Team finden
            $facebookPage = IntegrationsFacebookPage::where('team_id', $teamId)
                ->where('is_active', true)
                ->first();

            if (!$facebookPage) {
                return [
                    'success' => false,
                    'external_post_id' => null,
                    'error' => 'Keine aktive Facebook Page für dieses Team gefunden. Bitte zuerst eine Facebook Page verbinden.',
                ];
            }

            $contractData = $contract->contract ?? [];
            $apiVersion = config('integrations.oauth2.providers.meta.api_version', '21.0');

            // Post-Daten aufbauen
            $postParams = [
                'access_token' => $accessToken,
            ];

            // Text zusammenbauen (text + hashtags)
            $text = $contractData['text'] ?? '';
            if (!empty($contractData['hashtags']) && is_array($contractData['hashtags'])) {
                $hashtagText = implode(' ', array_map(fn($h) => str_starts_with($h, '#') ? $h : "#{$h}", $contractData['hashtags']));
                $text = trim($text . "\n\n" . $hashtagText);
            }
            $postParams['message'] = $text;

            // Link hinzufügen
            if (!empty($contractData['link'])) {
                $postParams['link'] = $contractData['link'];
            }

            $url = "https://graph.facebook.com/{$apiVersion}/{$facebookPage->external_id}/feed";

            // Wenn ein Bild vorhanden ist, als Photo-Post publishen
            if (!empty($contractData['image_url'])) {
                $url = "https://graph.facebook.com/{$apiVersion}/{$facebookPage->external_id}/photos";
                $postParams['url'] = $contractData['image_url'];
            }

            $response = Http::post($url, $postParams);

            if ($response->failed()) {
                $error = $response->json()['error'] ?? [];
                $errorMessage = $error['message'] ?? 'Unbekannter Facebook API Fehler';
                Log::error('Facebook publishing failed', [
                    'contract_id' => $contract->id,
                    'page_id' => $facebookPage->external_id,
                    'error' => $error,
                ]);
                return [
                    'success' => false,
                    'external_post_id' => null,
                    'error' => "Facebook API Fehler: {$errorMessage}",
                ];
            }

            $data = $response->json();
            $postId = $data['id'] ?? $data['post_id'] ?? null;

            return [
                'success' => true,
                'external_post_id' => $postId,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('Facebook publishing exception', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Facebook Publishing fehlgeschlagen: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Published einen Contract auf Instagram.
     *
     * Verwendet den Container-basierten Publishing Flow:
     * 1. Media Container erstellen
     * 2. Container publishen
     *
     * @return array{success: bool, external_post_id: ?string, error: ?string}
     */
    public function publishToInstagram(BrandsSocialCardContract $contract, User $user, int $teamId): array
    {
        try {
            $accessToken = $this->metaService->getValidAccessTokenForUser($user);
            if (!$accessToken) {
                return [
                    'success' => false,
                    'external_post_id' => null,
                    'error' => 'Kein gültiger Meta Access Token gefunden. Bitte Meta über OAuth verbinden.',
                ];
            }

            // Instagram Account für das Team finden
            $instagramAccount = IntegrationsInstagramAccount::where('team_id', $teamId)
                ->where('is_active', true)
                ->first();

            if (!$instagramAccount) {
                return [
                    'success' => false,
                    'external_post_id' => null,
                    'error' => 'Kein aktiver Instagram Account für dieses Team gefunden. Bitte zuerst einen Instagram Account verbinden.',
                ];
            }

            $contractData = $contract->contract ?? [];
            $apiVersion = config('integrations.oauth2.providers.meta.api_version', '21.0');
            $igUserId = $instagramAccount->external_id;

            // Caption zusammenbauen (text + hashtags)
            $caption = $contractData['text'] ?? '';
            if (!empty($contractData['hashtags']) && is_array($contractData['hashtags'])) {
                $hashtagText = implode(' ', array_map(fn($h) => str_starts_with($h, '#') ? $h : "#{$h}", $contractData['hashtags']));
                $caption = trim($caption . "\n\n" . $hashtagText);
            }

            $format = $contract->platformFormat;
            $formatKey = $format->key ?? 'post';

            // Carousel-Publishing
            if ($formatKey === 'carousel' && !empty($contractData['slides']) && is_array($contractData['slides'])) {
                return $this->publishInstagramCarousel($igUserId, $caption, $contractData['slides'], $accessToken, $apiVersion, $contract);
            }

            // Video/Reel-Publishing
            if (in_array($formatKey, ['reel', 'video']) && !empty($contractData['video_url'])) {
                return $this->publishInstagramReel($igUserId, $caption, $contractData, $accessToken, $apiVersion, $contract);
            }

            // Single Image Post
            if (!empty($contractData['image_url'])) {
                return $this->publishInstagramPhoto($igUserId, $caption, $contractData['image_url'], $accessToken, $apiVersion, $contract);
            }

            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Kein image_url oder video_url im Contract vorhanden. Instagram erfordert ein Medium.',
            ];
        } catch (\Throwable $e) {
            Log::error('Instagram publishing exception', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Instagram Publishing fehlgeschlagen: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Instagram Photo Publishing (Container-based)
     */
    private function publishInstagramPhoto(string $igUserId, string $caption, string $imageUrl, string $accessToken, string $apiVersion, BrandsSocialCardContract $contract): array
    {
        // Step 1: Container erstellen
        $containerResponse = Http::post("https://graph.facebook.com/{$apiVersion}/{$igUserId}/media", [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $accessToken,
        ]);

        if ($containerResponse->failed()) {
            $error = $containerResponse->json()['error'] ?? [];
            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Instagram Container-Erstellung fehlgeschlagen: ' . ($error['message'] ?? 'Unbekannter Fehler'),
            ];
        }

        $containerId = $containerResponse->json()['id'] ?? null;
        if (!$containerId) {
            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Instagram Container-ID nicht erhalten.',
            ];
        }

        // Step 2: Container publishen
        return $this->publishInstagramContainer($igUserId, $containerId, $accessToken, $apiVersion, $contract);
    }

    /**
     * Instagram Reel/Video Publishing (Container-based)
     */
    private function publishInstagramReel(string $igUserId, string $caption, array $contractData, string $accessToken, string $apiVersion, BrandsSocialCardContract $contract): array
    {
        $containerParams = [
            'media_type' => 'REELS',
            'video_url' => $contractData['video_url'],
            'caption' => $caption,
            'access_token' => $accessToken,
        ];

        if (!empty($contractData['cover_image_url'])) {
            $containerParams['cover_url'] = $contractData['cover_image_url'];
        }

        $containerResponse = Http::post("https://graph.facebook.com/{$apiVersion}/{$igUserId}/media", $containerParams);

        if ($containerResponse->failed()) {
            $error = $containerResponse->json()['error'] ?? [];
            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Instagram Reel-Container fehlgeschlagen: ' . ($error['message'] ?? 'Unbekannter Fehler'),
            ];
        }

        $containerId = $containerResponse->json()['id'] ?? null;
        if (!$containerId) {
            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Instagram Container-ID nicht erhalten.',
            ];
        }

        return $this->publishInstagramContainer($igUserId, $containerId, $accessToken, $apiVersion, $contract);
    }

    /**
     * Instagram Carousel Publishing (Container-based)
     */
    private function publishInstagramCarousel(string $igUserId, string $caption, array $slides, string $accessToken, string $apiVersion, BrandsSocialCardContract $contract): array
    {
        $childContainerIds = [];

        // Step 1: Child Container für jedes Slide erstellen
        foreach ($slides as $slide) {
            $imageUrl = $slide['image_url'] ?? null;
            if (!$imageUrl) {
                continue;
            }

            $childResponse = Http::post("https://graph.facebook.com/{$apiVersion}/{$igUserId}/media", [
                'image_url' => $imageUrl,
                'is_carousel_item' => true,
                'access_token' => $accessToken,
            ]);

            if ($childResponse->failed()) {
                $error = $childResponse->json()['error'] ?? [];
                return [
                    'success' => false,
                    'external_post_id' => null,
                    'error' => 'Instagram Carousel-Slide fehlgeschlagen: ' . ($error['message'] ?? 'Unbekannter Fehler'),
                ];
            }

            $childId = $childResponse->json()['id'] ?? null;
            if ($childId) {
                $childContainerIds[] = $childId;
            }
        }

        if (count($childContainerIds) < 2) {
            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Instagram Carousel erfordert mindestens 2 Slides (nur ' . count($childContainerIds) . ' erstellt).',
            ];
        }

        // Step 2: Carousel Container erstellen
        $carouselResponse = Http::post("https://graph.facebook.com/{$apiVersion}/{$igUserId}/media", [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childContainerIds),
            'caption' => $caption,
            'access_token' => $accessToken,
        ]);

        if ($carouselResponse->failed()) {
            $error = $carouselResponse->json()['error'] ?? [];
            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Instagram Carousel-Container fehlgeschlagen: ' . ($error['message'] ?? 'Unbekannter Fehler'),
            ];
        }

        $containerId = $carouselResponse->json()['id'] ?? null;
        if (!$containerId) {
            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Instagram Carousel Container-ID nicht erhalten.',
            ];
        }

        // Step 3: Container publishen
        return $this->publishInstagramContainer($igUserId, $containerId, $accessToken, $apiVersion, $contract);
    }

    /**
     * Shared: Instagram Container publishen
     */
    private function publishInstagramContainer(string $igUserId, string $containerId, string $accessToken, string $apiVersion, BrandsSocialCardContract $contract): array
    {
        $publishResponse = Http::post("https://graph.facebook.com/{$apiVersion}/{$igUserId}/media_publish", [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ]);

        if ($publishResponse->failed()) {
            $error = $publishResponse->json()['error'] ?? [];
            Log::error('Instagram media_publish failed', [
                'contract_id' => $contract->id,
                'container_id' => $containerId,
                'error' => $error,
            ]);
            return [
                'success' => false,
                'external_post_id' => null,
                'error' => 'Instagram Publishing fehlgeschlagen: ' . ($error['message'] ?? 'Unbekannter Fehler'),
            ];
        }

        $mediaId = $publishResponse->json()['id'] ?? null;

        return [
            'success' => true,
            'external_post_id' => $mediaId,
            'error' => null,
        ];
    }
}
