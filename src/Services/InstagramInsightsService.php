<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsInstagramAccount;
use Platform\Brands\Models\BrandsInstagramAccountInsight;
use Platform\Brands\Models\BrandsInstagramMedia;
use Platform\Brands\Models\BrandsInstagramMediaInsight;
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
     * Ruft aktuelle Account-Details ab und speichert sie
     */
    public function fetchAccountDetails(BrandsInstagramAccount $account): array
    {
        $apiVersion = config('brands.meta.api_version', 'v21.0');
        $accessToken = $this->tokenService->getValidAccessToken($account->brand->metaToken);

        if (!$accessToken) {
            Log::error('No valid access token for account', ['account_id' => $account->id]);
            return [];
        }

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

        $data = $response->json();
        
        // Account Details in Insights speichern
        BrandsInstagramAccountInsight::updateOrCreate(
            [
                'instagram_account_id' => $account->id,
                'insight_date' => Carbon::now()->format('Y-m-d'),
            ],
            [
                'current_name' => $data['name'] ?? null,
                'current_username' => $data['username'] ?? null,
                'current_biography' => $data['biography'] ?? null,
                'current_profile_picture_url' => $data['profile_picture_url'] ?? null,
                'current_website' => $data['website'] ?? null,
                'current_followers' => $data['followers_count'] ?? null,
                'current_follows' => $data['follows_count'] ?? null,
            ]
        );

        Log::info('Account details fetched and saved', [
            'account_id' => $account->id,
            'followers' => $data['followers_count'] ?? 0,
            'follows' => $data['follows_count'] ?? 0,
        ]);

        return $data;
    }

    /**
     * Synchronisiert Account Insights (tägliche Metriken, Total-Value-Metriken, Account-Details)
     */
    public function syncAccountInsights(BrandsInstagramAccount $account): array
    {
        $insights = [];
        
        // Account Details (Follower/Following) holen
        $accountDetails = $this->fetchAccountDetails($account);
        if (!empty($accountDetails)) {
            $insights['account_details'] = $accountDetails;
        }

        // Tägliche Metriken holen
        $dailyMetrics = $this->fetchDailyMetrics($account);
        if (!empty($dailyMetrics)) {
            $this->saveDailyMetrics($account, $dailyMetrics);
            $insights['daily_metrics'] = $dailyMetrics;
        }

        // Total-Value-Metriken holen
        $totalValueMetrics = $this->fetchTotalValueMetrics($account);
        if (!empty($totalValueMetrics)) {
            $this->saveTotalValueMetrics($account, $totalValueMetrics);
            $insights['total_value_metrics'] = $totalValueMetrics;
        }

        return $insights;
    }

    /**
     * Speichert tägliche Metriken in der Datenbank
     */
    protected function saveDailyMetrics(BrandsInstagramAccount $account, array $metrics): void
    {
        $insightDate = Carbon::now()->format('Y-m-d');
        
        foreach ($metrics as $metricName => $values) {
            if (empty($values) || !is_array($values)) {
                continue;
            }

            // Für tägliche Metriken speichern wir jeden Tag-Wert
            foreach ($values as $valueData) {
                $value = is_array($valueData) ? ($valueData['value'] ?? 0) : $valueData;
                $endTime = is_array($valueData) && isset($valueData['end_time']) 
                    ? Carbon::parse($valueData['end_time'])->format('Y-m-d')
                    : $insightDate;

                BrandsInstagramAccountInsight::updateOrCreate(
                    [
                        'instagram_account_id' => $account->id,
                        'insight_date' => $endTime,
                    ],
                    [
                        $metricName => $value,
                    ]
                );
            }
        }
    }

    /**
     * Speichert Total-Value-Metriken in der Datenbank
     */
    protected function saveTotalValueMetrics(BrandsInstagramAccount $account, array $metrics): void
    {
        $insightDate = Carbon::now()->format('Y-m-d');
        
        foreach ($metrics as $metricName => $values) {
            if (empty($values) || !is_array($values)) {
                continue;
            }

            // Für Total-Value-Metriken nehmen wir den ersten Wert (total_value)
            $totalValue = isset($values[0]) && is_array($values[0]) 
                ? ($values[0] ?? 0)
                : (isset($values[0]) ? $values[0] : 0);

            BrandsInstagramAccountInsight::updateOrCreate(
                [
                    'instagram_account_id' => $account->id,
                    'insight_date' => $insightDate,
                ],
                [
                    $metricName => $totalValue,
                ]
            );
        }
    }

    /**
     * Synchronisiert Media Insights für alle Media eines Accounts
     */
    public function syncMediaInsights(BrandsInstagramAccount $account): array
    {
        $mediaItems = BrandsInstagramMedia::where('instagram_account_id', $account->id)
            ->where('insights_available', true)
            ->get();

        $syncedCount = 0;
        $skippedCount = 0;

        foreach ($mediaItems as $media) {
            try {
                $insights = $this->fetchMediaInsights(
                    $media->external_id,
                    $account,
                    strtolower($media->media_type)
                );

                if (isset($insights['insights_available']) && !$insights['insights_available']) {
                    $media->update(['insights_available' => false]);
                    $skippedCount++;
                    continue;
                }

                if (!empty($insights)) {
                    $this->saveMediaInsights($media, $insights);
                    $syncedCount++;
                }
            } catch (\Exception $e) {
                Log::error('Error syncing media insights', [
                    'media_id' => $media->id,
                    'error' => $e->getMessage(),
                ]);
                $skippedCount++;
            }
        }

        return [
            'synced' => $syncedCount,
            'skipped' => $skippedCount,
        ];
    }

    /**
     * Speichert Media Insights in der Datenbank
     */
    protected function saveMediaInsights(BrandsInstagramMedia $media, array $insights): void
    {
        $insightDate = Carbon::now()->format('Y-m-d');

        BrandsInstagramMediaInsight::updateOrCreate(
            [
                'instagram_media_id' => $media->id,
                'insight_date' => $insightDate,
            ],
            $insights
        );
    }

    /**
     * Ruft Media-Insights für ein einzelnes Media ab
     */
    public function fetchMediaInsights(string $mediaId, BrandsInstagramAccount $account, string $mediaType = 'photo'): array
    {
        $apiVersion = config('brands.meta.api_version', 'v21.0');
        $accessToken = $this->tokenService->getValidAccessToken($account->brand->metaToken);

        if (!$accessToken) {
            Log::error('No valid access token for account', ['account_id' => $account->id]);
            return [];
        }

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
        $accessToken = $this->tokenService->getValidAccessToken($account->brand->metaToken);

        if (!$accessToken) {
            Log::error('No valid access token for account', ['account_id' => $account->id]);
            return [];
        }

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
