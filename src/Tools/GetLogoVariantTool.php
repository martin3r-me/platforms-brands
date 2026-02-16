<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsLogoVariant;
use Illuminate\Support\Facades\Gate;

class GetLogoVariantTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.logo_variant.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/logo_variants/{id} - Gibt eine einzelne Logo-Variante zurück. REST-Parameter: variant_id (required, integer) - Varianten-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'variant_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Logo-Variante (ERFORDERLICH). Nutze "brands.logo_variants.GET" um Varianten zu finden.'
                ],
            ],
            'required' => ['variant_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $variantId = $arguments['variant_id'] ?? null;
            if (!$variantId) {
                return ToolResult::error('VALIDATION_ERROR', 'variant_id ist erforderlich.');
            }

            $variant = BrandsLogoVariant::with('logoBoard')->find($variantId);
            if (!$variant) {
                return ToolResult::error('VARIANT_NOT_FOUND', 'Die angegebene Logo-Variante wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $variant)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Logo-Variante.');
            }

            return ToolResult::success([
                'id' => $variant->id,
                'uuid' => $variant->uuid,
                'name' => $variant->name,
                'type' => $variant->type,
                'type_label' => $variant->type_label,
                'description' => $variant->description,
                'usage_guidelines' => $variant->usage_guidelines,
                'file_name' => $variant->file_name,
                'file_format' => $variant->file_format,
                'additional_formats' => $variant->additional_formats,
                'clearspace_factor' => $variant->clearspace_factor,
                'min_width_px' => $variant->min_width_px,
                'min_width_mm' => $variant->min_width_mm,
                'background_color' => $variant->background_color,
                'dos' => $variant->dos,
                'donts' => $variant->donts,
                'order' => $variant->order,
                'logo_board_id' => $variant->logo_board_id,
                'logo_board_name' => $variant->logoBoard->name,
                'created_at' => $variant->created_at->toIso8601String(),
                'updated_at' => $variant->updated_at->toIso8601String(),
                'message' => "Logo-Variante '{$variant->name}' geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Logo-Variante: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'logo_variant', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
