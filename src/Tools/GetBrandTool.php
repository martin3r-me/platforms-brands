<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen einer einzelnen Marke
 */
class GetBrandTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.brand.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/{id} - Ruft eine einzelne Marke ab. REST-Parameter: id (required, integer) - Marken-ID. Nutze "brands.brands.GET" um verfügbare Marken-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Marke. Nutze "brands.brands.GET" um verfügbare Marken-IDs zu sehen.'
                ]
            ],
            'required' => ['id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Marken-ID ist erforderlich. Nutze "brands.brands.GET" um Marken zu finden.');
            }

            // Marke holen
            $brand = BrandsBrand::with(['user', 'team'])
                ->find($arguments['id']);

            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Marke wurde nicht gefunden. Nutze "brands.brands.GET" um alle verfügbaren Marken zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Marke (Policy).');
            }

            return ToolResult::success([
                'id' => $brand->id,
                'uuid' => $brand->uuid,
                'name' => $brand->name,
                'description' => $brand->description,
                'team_id' => $brand->team_id,
                'user_id' => $brand->user_id,
                'done' => $brand->done,
                'done_at' => $brand->done_at?->toIso8601String(),
                'created_at' => $brand->created_at->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Marke: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'brand', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
