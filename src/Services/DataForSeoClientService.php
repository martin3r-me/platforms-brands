<?php

namespace Platform\Brands\Services;

class DataForSeoClientService
{
    /**
     * Prüft ob DataForSEO konfiguriert ist.
     */
    public function isConfigured(): bool
    {
        try {
            $client = app('dataforseo.client');
            return $client !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Keyword-Metriken von DataForSEO abrufen.
     *
     * @return array Keyword => Metriken
     */
    public function fetchKeywordMetrics(array $keywords, string $location = 'Germany', string $language = 'de'): array
    {
        if (!$this->isConfigured() || empty($keywords)) {
            return [];
        }

        try {
            $client = app('dataforseo.client');

            $postData = [
                [
                    'keywords' => $keywords,
                    'location_name' => $location,
                    'language_name' => $language,
                ],
            ];

            $response = $client->post('/v3/keywords_data/google_ads/search_volume/live', $postData);

            $results = [];
            if (!empty($response['tasks'][0]['result'])) {
                foreach ($response['tasks'][0]['result'] as $item) {
                    $results[$item['keyword']] = [
                        'search_volume' => $item['search_volume'] ?? null,
                        'cpc' => isset($item['cpc']) ? (int) round($item['cpc'] * 100) : null,
                        'competition' => $item['competition'] ?? null,
                        'keyword_difficulty' => $item['keyword_info']['keyword_difficulty'] ?? null,
                        'trend' => $item['monthly_searches'] ?? null,
                    ];
                }
            }

            return $results;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DataForSEO fetch failed', [
                'error' => $e->getMessage(),
                'keywords_count' => count($keywords),
            ]);
            return [];
        }
    }

    /**
     * Geschätzte Kosten für eine Keyword-Abfrage.
     */
    public function estimateCost(int $keywordCount): int
    {
        // DataForSEO Pricing: ca. $0.05 pro Keyword für Search Volume
        return (int) ceil($keywordCount * 5);
    }
}
