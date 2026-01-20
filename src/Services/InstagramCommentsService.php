<?php

namespace Platform\Brands\Services;

use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service für Instagram Comments Management
 */
class InstagramCommentsService
{
    // Token wird als Parameter übergeben, kein Service nötig

    /**
     * Ruft alle Comments für ein Instagram Media ab
     * 
     * @param string $mediaId Instagram Media ID
     * @param string $accessToken Access Token
     * @return array Array mit Comment-Daten
     */
    public function fetchComments(string $mediaId, string $accessToken): array
    {
        $apiVersion = config('integrations.oauth2.providers.meta.api_version', '21.0');
        $allComments = [];

        try {
            $response = Http::get("https://graph.facebook.com/{$apiVersion}/{$mediaId}/comments", [
                'fields' => 'id,text,username,like_count,timestamp',
                'access_token' => $accessToken,
            ]);

            if ($response->failed()) {
                $error = $response->json()['error'] ?? [];
                Log::error('Failed to fetch comments', [
                    'media_id' => $mediaId,
                    'error' => $error,
                ]);
                return [];
            }

            $data = $response->json();
            $comments = $data['data'] ?? [];

            foreach ($comments as $comment) {
                $allComments[] = $this->normalizeCommentData($comment);
            }

            // Pagination unterstützen
            $nextUrl = $data['paging']['next'] ?? null;
            while ($nextUrl) {
                $nextResponse = Http::get($nextUrl);

                if ($nextResponse->successful()) {
                    $nextData = $nextResponse->json();
                    $nextComments = $nextData['data'] ?? [];

                    foreach ($nextComments as $comment) {
                        $allComments[] = $this->normalizeCommentData($comment);
                    }

                    $nextUrl = $nextData['paging']['next'] ?? null;
                } else {
                    break;
                }
            }

            Log::info('Instagram comments fetched', [
                'media_id' => $mediaId,
                'count' => count($allComments),
            ]);

        } catch (\Exception $e) {
            Log::error('Exception fetching comments', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);
        }

        return $allComments;
    }

    /**
     * Normalisiert Comment-Daten für einheitliche Struktur
     */
    protected function normalizeCommentData(array $comment): array
    {
        return [
            'comment_id' => $comment['id'],
            'text' => $comment['text'] ?? null,
            'username' => $comment['username'] ?? null,
            'like_count' => $comment['like_count'] ?? 0,
            'timestamp' => isset($comment['timestamp']) 
                ? Carbon::parse($comment['timestamp'])->format('Y-m-d H:i:s')
                : null,
        ];
    }
}
