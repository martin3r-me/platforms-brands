<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsLogoBoard;
use Platform\Brands\Models\BrandsLogoVariant;
use Illuminate\Support\Facades\Gate;

class ListLogoVariantsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.logo_variants.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/logo_boards/{logo_board_id}/variants - Listet Logo-Varianten eines Boards auf. REST-Parameter: logo_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'logo_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Logo Boards. Nutze "brands.logo_boards.GET" um Boards zu finden.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $boardId = $arguments['logo_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'logo_board_id ist erforderlich.');
            }

            $board = BrandsLogoBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('LOGO_BOARD_NOT_FOUND', 'Das angegebene Logo Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Logo Board.');
            }

            $query = BrandsLogoVariant::query()
                ->where('logo_board_id', $boardId)
                ->with('logoBoard');

            $this->applyStandardFilters($query, $arguments, [
                'name', 'type', 'file_format', 'created_at', 'updated_at'
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'description', 'usage_guidelines']);
            $this->applyStandardSort($query, $arguments, [
                'name', 'type', 'order', 'created_at', 'updated_at'
            ], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $variants = $query->get();

            $variantsList = $variants->map(function ($variant) {
                return [
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
                    'created_at' => $variant->created_at->toIso8601String(),
                    'updated_at' => $variant->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'variants' => $variantsList,
                'count' => count($variantsList),
                'logo_board_id' => $boardId,
                'logo_board_name' => $board->name,
                'message' => count($variantsList) > 0
                    ? count($variantsList) . ' Logo-Variante(n) gefunden für Board "' . $board->name . '".'
                    : 'Keine Logo-Varianten gefunden für Board "' . $board->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Logo-Varianten: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'logo_variant', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
