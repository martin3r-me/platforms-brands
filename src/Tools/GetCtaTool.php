<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsCta;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen CTA (Call-to-Action)
 */
class GetCtaTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.cta.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/ctas/{id} - Ruft einen einzelnen CTA (Call-to-Action) ab inkl. Analytics-Daten (Impressions, Clicks, Conversion Rate). Ein CTA ist eine Handlungsaufforderung (z.B. "Jetzt anfragen") mit Typ, Funnel-Stage und optionaler Zielseite. REST-Parameter: id (required, integer) - CTA-ID. Nutze "brands.ctas.GET" um verfügbare CTA-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des CTA. Nutze "brands.ctas.GET" um verfügbare CTA-IDs zu sehen.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'CTA-ID ist erforderlich. Nutze "brands.ctas.GET" um CTAs zu finden.');
            }

            $cta = BrandsCta::with(['brand', 'targetPage.contentBoard', 'user', 'team'])
                ->find($arguments['id']);

            if (!$cta) {
                return ToolResult::error('CTA_NOT_FOUND', 'Der angegebene CTA wurde nicht gefunden. Nutze "brands.ctas.GET" um alle verfügbaren CTAs zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $cta);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen CTA (Policy).');
            }

            $data = [
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
                'impressions' => $cta->impressions,
                'clicks' => $cta->clicks,
                'conversion_rate' => $cta->conversion_rate,
                'last_clicked_at' => $cta->last_clicked_at?->toIso8601String(),
                'page_context_url' => $cta->getPageContextUrl(),
                'tracking_url' => route('brands.track.cta.click', ['uuid' => $cta->uuid]),
                'created_at' => $cta->created_at->toIso8601String(),
                'updated_at' => $cta->updated_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des CTA: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'cta', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
