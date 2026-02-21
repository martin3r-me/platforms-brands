<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Models\BrandsSeoKeywordPosition;
use Illuminate\Support\Facades\Gate;

class ListSeoKeywordPositionsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_positions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/seo_keywords/{seo_keyword_id}/positions - Ranking-History eines Keywords abrufen. Zeigt Position-Snapshots über Zeit mit Delta zur vorherigen Position. REST-Parameter: seo_keyword_id (required, integer). from/to (optional, ISO-8601 Datum für Zeitraum-Filter, z.B. "2025-01-01"). search_engine (optional: google|bing). device (optional: desktop|mobile). limit (optional, Standard: 50). Beispiel: Alle Positionen der letzten 30 Tage für ein Keyword abrufen um Ranking-Trends zu erkennen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_keyword_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SEO Keywords (ERFORDERLICH).'
                ],
                'from' => [
                    'type' => 'string',
                    'description' => 'Optional: Start-Datum für Zeitraum-Filter (ISO-8601, z.B. "2025-01-01").'
                ],
                'to' => [
                    'type' => 'string',
                    'description' => 'Optional: End-Datum für Zeitraum-Filter (ISO-8601, z.B. "2025-02-01").'
                ],
                'search_engine' => [
                    'type' => 'string',
                    'description' => 'Optional: Filter nach Suchmaschine (google, bing). Standard: alle.'
                ],
                'device' => [
                    'type' => 'string',
                    'description' => 'Optional: Filter nach Gerät (desktop, mobile). Standard: alle.'
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Optional: Maximale Anzahl der Ergebnisse. Standard: 50, Maximum: 500.'
                ],
            ],
            'required' => ['seo_keyword_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $seoKeywordId = $arguments['seo_keyword_id'] ?? null;
            if (!$seoKeywordId) {
                return ToolResult::error('VALIDATION_ERROR', 'seo_keyword_id ist erforderlich.');
            }

            $seoKeyword = BrandsSeoKeyword::find($seoKeywordId);
            if (!$seoKeyword) {
                return ToolResult::error('SEO_KEYWORD_NOT_FOUND', 'Das angegebene SEO Keyword wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $seoKeyword)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses SEO Keyword.');
            }

            $query = BrandsSeoKeywordPosition::query()
                ->where('seo_keyword_id', $seoKeywordId)
                ->orderByDesc('tracked_at');

            // Zeitraum-Filter
            if (!empty($arguments['from'])) {
                $query->where('tracked_at', '>=', $arguments['from']);
            }
            if (!empty($arguments['to'])) {
                $query->where('tracked_at', '<=', $arguments['to']);
            }

            // Search Engine Filter
            if (!empty($arguments['search_engine'])) {
                $query->where('search_engine', $arguments['search_engine']);
            }

            // Device Filter
            if (!empty($arguments['device'])) {
                $query->where('device', $arguments['device']);
            }

            $limit = min($arguments['limit'] ?? 50, 500);
            $query->limit($limit);

            $positions = $query->get();

            $positionsList = $positions->map(function ($pos) {
                $delta = null;
                if ($pos->previous_position !== null) {
                    $delta = $pos->previous_position - $pos->position; // positive = aufgestiegen
                }

                return [
                    'id' => $pos->id,
                    'uuid' => $pos->uuid,
                    'position' => $pos->position,
                    'previous_position' => $pos->previous_position,
                    'delta' => $delta,
                    'trend' => $delta !== null ? ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'stable')) : null,
                    'serp_features' => $pos->serp_features,
                    'tracked_at' => $pos->tracked_at->toIso8601String(),
                    'search_engine' => $pos->search_engine,
                    'device' => $pos->device,
                    'location' => $pos->location,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'seo_keyword_id' => $seoKeyword->id,
                'keyword' => $seoKeyword->keyword,
                'current_position' => $seoKeyword->position,
                'positions' => $positionsList,
                'count' => count($positionsList),
                'message' => count($positionsList) > 0
                    ? count($positionsList) . ' Position-Snapshot(s) für "' . $seoKeyword->keyword . '" gefunden.'
                    : 'Keine Position-Snapshots für dieses Keyword vorhanden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Keyword-Positionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'seo_keyword', 'position', 'ranking', 'history'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
