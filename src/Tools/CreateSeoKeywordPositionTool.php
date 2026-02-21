<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSeoKeyword;
use Platform\Brands\Models\BrandsSeoKeywordPosition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateSeoKeywordPositionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.seo_keyword_positions.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/seo_keywords/{seo_keyword_id}/positions - Erfasst einen neuen Ranking-Position-Snapshot für ein Keyword. Wird auch automatisch bei FETCH_METRICS aufgerufen. REST-Parameter: seo_keyword_id (required, integer), position (required, integer), search_engine (optional, default: google), device (optional: desktop|mobile, default: desktop), location (optional, string), serp_features (optional, array von SERP-Features wie "featured_snippet", "local_pack", "faq", "sitelinks"). Die previous_position wird automatisch aus dem letzten Snapshot ermittelt.';
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
                'position' => [
                    'type' => 'integer',
                    'description' => 'Aktuelle Ranking-Position (ERFORDERLICH, z.B. 1-100).'
                ],
                'search_engine' => [
                    'type' => 'string',
                    'description' => 'Optional: Suchmaschine (google, bing). Standard: google.'
                ],
                'device' => [
                    'type' => 'string',
                    'enum' => ['desktop', 'mobile'],
                    'description' => 'Optional: Gerätetyp (desktop, mobile). Standard: desktop.'
                ],
                'location' => [
                    'type' => 'string',
                    'description' => 'Optional: Lokaler Kontext der Messung (z.B. "München", "Deutschland").'
                ],
                'serp_features' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Optional: SERP-Features (z.B. ["featured_snippet", "local_pack", "faq", "sitelinks"]).'
                ],
            ],
            'required' => ['seo_keyword_id', 'position']
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

            $position = $arguments['position'] ?? null;
            if ($position === null) {
                return ToolResult::error('VALIDATION_ERROR', 'position ist erforderlich.');
            }

            $seoKeyword = BrandsSeoKeyword::find($seoKeywordId);
            if (!$seoKeyword) {
                return ToolResult::error('SEO_KEYWORD_NOT_FOUND', 'Das angegebene SEO Keyword wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $seoKeyword);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Position-Snapshots für dieses Keyword erstellen (Policy).');
            }

            // Device validieren
            $device = $arguments['device'] ?? 'desktop';
            if (!in_array($device, ['desktop', 'mobile'], true)) {
                return ToolResult::error('VALIDATION_ERROR', 'device muss "desktop" oder "mobile" sein.');
            }

            $searchEngine = $arguments['search_engine'] ?? 'google';

            // Vorherige Position ermitteln
            $lastSnapshot = BrandsSeoKeywordPosition::where('seo_keyword_id', $seoKeywordId)
                ->where('search_engine', $searchEngine)
                ->where('device', $device)
                ->orderByDesc('tracked_at')
                ->first();

            $previousPosition = $lastSnapshot?->position;

            $snapshot = BrandsSeoKeywordPosition::create([
                'seo_keyword_id' => $seoKeywordId,
                'position' => $position,
                'previous_position' => $previousPosition,
                'serp_features' => $arguments['serp_features'] ?? null,
                'tracked_at' => now(),
                'search_engine' => $searchEngine,
                'device' => $device,
                'location' => $arguments['location'] ?? null,
            ]);

            // Keyword-Position ebenfalls aktualisieren
            $seoKeyword->update(['position' => $position]);

            $delta = $previousPosition !== null ? ($previousPosition - $position) : null;

            return ToolResult::success([
                'id' => $snapshot->id,
                'uuid' => $snapshot->uuid,
                'seo_keyword_id' => $seoKeyword->id,
                'keyword' => $seoKeyword->keyword,
                'position' => $snapshot->position,
                'previous_position' => $snapshot->previous_position,
                'delta' => $delta,
                'trend' => $delta !== null ? ($delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'stable')) : null,
                'search_engine' => $snapshot->search_engine,
                'device' => $snapshot->device,
                'location' => $snapshot->location,
                'serp_features' => $snapshot->serp_features,
                'tracked_at' => $snapshot->tracked_at->toIso8601String(),
                'message' => "Position-Snapshot für '{$seoKeyword->keyword}' erfasst: Position {$position}"
                    . ($delta !== null ? " (Delta: " . ($delta > 0 ? "+{$delta}" : $delta) . ")" : ' (erster Snapshot)')
                    . "."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Position-Snapshots: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'seo_keyword', 'position', 'ranking', 'snapshot', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
