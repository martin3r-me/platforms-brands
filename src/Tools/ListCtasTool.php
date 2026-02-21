<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCta;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von CTAs (Call-to-Actions) im Brands-Modul.
 *
 * Unterstützt Filterung nach brand_id, type, funnel_stage, target_page_id und is_active.
 */
class ListCtasTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.ctas.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/ctas - Listet CTAs (Call-to-Actions) einer Brand auf. Ein CTA ist eine Handlungsaufforderung (z.B. "Jetzt anfragen") mit Typ und Funnel-Stage. Filterbar nach brand_id, type (primary|secondary|micro), funnel_stage (awareness|consideration|decision), target_page_id, is_active. REST-Parameter: brand_id (required), filters (optional), search (optional), sort (optional), limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'brand_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID der Brand. Nutze "brands.brands.GET" um Brands zu finden.',
                    ],
                    'type' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach CTA-Typ. Mögliche Werte: "primary", "secondary", "micro".',
                        'enum' => ['primary', 'secondary', 'micro'],
                    ],
                    'funnel_stage' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach Funnel-Stage. Mögliche Werte: "awareness", "consideration", "decision".',
                        'enum' => ['awareness', 'consideration', 'decision'],
                    ],
                    'target_page_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Zielseite (Content Board Block-ID).',
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Filter nach Aktivstatus. true = nur aktive, false = nur inaktive CTAs.',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $brandId = $arguments['brand_id'] ?? null;
            if (!$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'brand_id ist erforderlich.');
            }

            $brand = BrandsBrand::find($brandId);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Brand wurde nicht gefunden.');
            }

            // Policy prüfen
            if (!Gate::forUser($context->user)->allows('view', $brand)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Brand.');
            }

            // Query aufbauen
            $query = BrandsCta::query()
                ->where('brand_id', $brandId)
                ->with(['brand', 'targetPage', 'user', 'team']);

            // Spezifische Filter
            if (isset($arguments['type'])) {
                $query->where('type', $arguments['type']);
            }

            if (isset($arguments['funnel_stage'])) {
                $query->where('funnel_stage', $arguments['funnel_stage']);
            }

            if (isset($arguments['target_page_id'])) {
                $query->where('target_page_id', $arguments['target_page_id']);
            }

            if (isset($arguments['is_active'])) {
                $query->where('is_active', (bool) $arguments['is_active']);
            }

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'label', 'type', 'funnel_stage', 'is_active', 'created_at', 'updated_at',
            ]);

            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['label', 'description']);

            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'label', 'type', 'funnel_stage', 'is_active', 'created_at', 'updated_at',
            ], 'created_at', 'desc');

            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            // CTAs holen und per Policy filtern
            $ctas = $query->get()->filter(function ($cta) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $cta);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            // CTAs formatieren
            $ctasList = $ctas->map(function ($cta) {
                return [
                    'id' => $cta->id,
                    'uuid' => $cta->uuid,
                    'label' => $cta->label,
                    'description' => $cta->description,
                    'type' => $cta->type,
                    'funnel_stage' => $cta->funnel_stage,
                    'target_page_id' => $cta->target_page_id,
                    'target_page_name' => $cta->targetPage?->name,
                    'target_url' => $cta->target_url,
                    'is_active' => $cta->is_active,
                    'brand_id' => $cta->brand_id,
                    'brand_name' => $cta->brand->name,
                    'team_id' => $cta->team_id,
                    'user_id' => $cta->user_id,
                    'created_at' => $cta->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'ctas' => $ctasList,
                'count' => count($ctasList),
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'message' => count($ctasList) > 0
                    ? count($ctasList) . ' CTA(s) gefunden für Brand "' . $brand->name . '".'
                    : 'Keine CTAs gefunden für Brand "' . $brand->name . '".',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der CTAs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'cta', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
