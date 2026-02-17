<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsCompetitor;
use Illuminate\Support\Facades\Gate;

class GetCompetitorTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.competitor.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/competitors/{id} - Gibt einen einzelnen Wettbewerber zurück. REST-Parameter: competitor_id (required, integer) - Wettbewerber-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'competitor_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Wettbewerbers (ERFORDERLICH). Nutze "brands.competitors.GET" um Wettbewerber zu finden.'
                ],
            ],
            'required' => ['competitor_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $competitorId = $arguments['competitor_id'] ?? null;
            if (!$competitorId) {
                return ToolResult::error('VALIDATION_ERROR', 'competitor_id ist erforderlich.');
            }

            $competitor = BrandsCompetitor::with(['competitorBoard'])->find($competitorId);
            if (!$competitor) {
                return ToolResult::error('COMPETITOR_NOT_FOUND', 'Der angegebene Wettbewerber wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $competitor)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Wettbewerber.');
            }

            return ToolResult::success([
                'id' => $competitor->id,
                'uuid' => $competitor->uuid,
                'name' => $competitor->name,
                'logo_url' => $competitor->logo_url,
                'website_url' => $competitor->website_url,
                'description' => $competitor->description,
                'strengths' => $competitor->strengths,
                'weaknesses' => $competitor->weaknesses,
                'notes' => $competitor->notes,
                'position_x' => $competitor->position_x,
                'position_y' => $competitor->position_y,
                'is_own_brand' => $competitor->is_own_brand,
                'differentiation' => $competitor->differentiation,
                'order' => $competitor->order,
                'competitor_board_id' => $competitor->competitor_board_id,
                'competitor_board_name' => $competitor->competitorBoard->name,
                'created_at' => $competitor->created_at->toIso8601String(),
                'updated_at' => $competitor->updated_at->toIso8601String(),
                'message' => "Wettbewerber '{$competitor->name}' geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Wettbewerbers: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'competitor', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
