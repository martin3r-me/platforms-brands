<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsLogoBoard;
use Illuminate\Support\Facades\Gate;

class GetLogoBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.logo_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/logo_boards/{id} - Gibt ein einzelnes Logo Board zurück inkl. aller Varianten. REST-Parameter: logo_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'logo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Logo Boards (ERFORDERLICH). Nutze "brands.logo_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['logo_board_id']
        ];
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

            $board = BrandsLogoBoard::with(['brand', 'variants', 'user', 'team'])->find($boardId);
            if (!$board) {
                return ToolResult::error('LOGO_BOARD_NOT_FOUND', 'Das angegebene Logo Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Logo Board.');
            }

            $variants = $board->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'uuid' => $variant->uuid,
                    'name' => $variant->name,
                    'type' => $variant->type,
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
                ];
            })->toArray();

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'team_id' => $board->team_id,
                'done' => $board->done,
                'variants' => $variants,
                'variants_count' => count($variants),
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Logo Board '{$board->name}' mit " . count($variants) . " Varianten geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Logo Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'logo_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
