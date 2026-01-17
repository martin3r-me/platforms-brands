<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsInstagramAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service für Instagram Insights/Metriken
 */
class InstagramInsightsService
{
    protected MetaTokenService $tokenService;

    public function __construct(MetaTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Ruft tägliche Metriken für einen Account ab
     */
    public function fetchDailyMetrics(BrandsInstagramAccount $account): array
    {
        $metrics = ['follower_count', 'impressions', 'reach'];
        return $this->fetchAccountInsights($account, $metrics, 'day');
    }

    /**
     * Ruft Total-Value-Metriken für einen Account ab
     */
    public function fetchTotalValueMetrics(BrandsInstagramAccount $account): array
    {
        $metrics = ['accounts_engaged', 'total_interactions', 'likes', 'comments', 'shares', 'saves', 'replies'];
        return $this->fetchAccountInsights($account, $metrics, 'day', 'total_value');
    }

    /**
     * Ruft demografische Metriken für einen Account ab
     */
    public function fetchDemographics(BrandsInstagramAccount $account, array $breakdowns = ['age', 'city', 'country', 'gender']): array
    {
        $metrics = ['follower_demographics', 'engaged_audience_demographics', 'reached_audience_demographics'];
        $apiVersion = config('brands.meta.api_version', 'v21.0');
        $accessToken = $account->access_token;
        $allData = [];

        foreach ($metrics as $metric) {
            foreach ($breakdowns as $breakdown) {
                try {
                    $response = Http::get("https://graph.facebook.com/{$apiVersion}/{$account->external_id}/insights", [
                        'metric' => $metric,
                        'metric_type' => 'total_value',
                        'breakdown' => $breakdown,
                        'period' => 'lifetime',
                        'timeframe' => 'this_month',
                        'access_token' => $accessToken,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $allData["{$metric}_{$breakdown}"] = $data;
                    } else {
                        Log::warning('Failed to fetch demographic insights', [
                            'account_id' => $account->id,
                            'metric' => $metric,
                            'breakdown' => $breakdown,
                            'error' => $response->json()['error'] ?? [],
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Exception fetching demographic insights', [
                        'account_id' => $account->id,
                        'metric' => $metric,
                        'breakdown' => $breakdown,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $allData;
    }

    /**
     * Ruft aktuelle Account-Details ab
     */
    public function fetchAccountDetails(BrandsInstagramAccount $account): array
    {
        $apiVersion = config('brands.meta.api_version', 'v21.0');
        $accessToken = $account->access_token;

        $response = Http::get("https://graph.facebook.com/{$apiVersion}/{$account->external_id}", [
            'fields' => 'name,username,biography,profile_picture_url,website,followers_count,follows_count',
            'access_token' => $accessToken,
        ]);

        if ($response->failed()) {
            $error = $response->json()['error'] ?? [];
            Log::error('Failed to fetch account details', [
                'account_id' => $account->id,
                'error' => $error,
            ]);
            return [];
        }

        return $response->json();
    }

    /**
     * Ruft Media-Insights für ein einzelnes Media ab
     */
    public function fetchMediaInsights(string $mediaId, string $accessToken, string $mediaType = 'photo'): array
    {
        $apiVersion = config('brands.meta.api_version', 'v21.0');
        $metrics = [
            'impressions',
            'reach',
            'saved',
            'comments',
            'likes',
            'shares',
            'total_interactions',
        ];

        // Zusätzliche Metriken für Stories
        if ($mediaType === 'story') {
            $metrics = array_merge($metrics, ['replies', 'navigation']);
        }

        // Zusätzliche Metriken für Reels
        if ($mediaType === 'reel') {
            $metrics = array_merge($metrics, [
                'plays',
                'clips_replays_count',
                'ig_reels_aggregated_all_plays_count',
                'ig_reels_avg_watch_time',
                'ig_reels_video_view_total_time',
            ]);
        }

        $params = [
            'metric' => implode(',', $metrics),
            'access_token' => $accessToken,
        ];

        if ($mediaType === 'story') {
            $params['breakdown'] = 'story_navigation_action_type';
        }

        $response = Http::get("https://graph.facebook.com/{$apiVersion}/{$mediaId}/insights", $params);

        if ($response->failed()) {
            $error = $response->json()['error'] ?? [];
            
            // Spezieller Fehler: Insights nicht verfügbar (vor Business Account Conversion)
            if (isset($error['error_subcode']) && $error['error_subcode'] === 2108006) {
                Log::info('Media insights not available', [
                    'media_id' => $mediaId,
                    'reason' => 'Posted before Business Account conversion',
                ]);
                return ['insights_available' => false];
            }

            Log::error('Failed to fetch media insights', [
                'media_id' => $mediaId,
                'error' => $error,
            ]);
            return [];
        }

        $data = $response->json();
        $insights = [];

        if (isset($data['data'])) {
            foreach ($data['data'] as $insight) {
                $metricName = $insight['name'];
                foreach ($insight['values'] as $value) {
                    $insights[$metricName] = $value['value'] ?? 0;
                }
            }
        }

        return $insights;
    }

    /**
     * Generische Methode zum Abrufen von Account-Insights
     */
    protected function fetchAccountInsights(BrandsInstagramAccount $account, array $metrics, string $period = 'day', ?string $metricType = null): array
    {
        $apiVersion = config('brands.meta.api_version', 'v21.0');
        $accessToken = $account->access_token;

        $params = [
            'metric' => implode(',', $metrics),
            'period' => $period,
            'access_token' => $accessToken,
        ];

        if ($metricType) {
            $params['metric_type'] = $metricType;
        }

        $response = Http::get("https://graph.facebook.com/{$apiVersion}/{$account->external_id}/insights", $params);

        if ($response->failed()) {
            $error = $response->json()['error'] ?? [];
            Log::error('Failed to fetch account insights', [
                'account_id' => $account->id,
                'metrics' => $metrics,
                'error' => $error,
            ]);
            return [];
        }

        $data = $response->json();
        $insights = [];

        if (isset($data['data'])) {
            foreach ($data['data'] as $insight) {
                $metricName = $insight['name'];
                $values = [];

                foreach ($insight['values'] as $value) {
                    if ($metricType === 'total_value') {
                        $values[] = $value['total_value']['value'] ?? 0;
                    } else {
                        $values[] = [
                            'value' => $value['value'] ?? 0,
                            'end_time' => $value['end_time'] ?? null,
                        ];
                    }
                }

                $insights[$metricName] = $values;
            }
        }

        return $insights;
    }
}
