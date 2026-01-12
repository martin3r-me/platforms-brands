<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsCiBoard;
use Platform\Brands\Models\BrandsCiBoardColor;
use Illuminate\Support\Facades\Gate;

class ListCiBoardColorsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.ci_board_colors.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/ci_boards/{ci_board_id}/colors - Listet Farben eines CI Boards auf. REST-Parameter: ci_board_id (required, integer) - CI Board-ID.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'ci_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des CI Boards. Nutze "brands.ci_boards.GET" um CI Boards zu finden.'
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

            $ciBoardId = $arguments['ci_board_id'] ?? null;
            if (!$ciBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'ci_board_id ist erforderlich.');
            }

            $ciBoard = BrandsCiBoard::find($ciBoardId);
            if (!$ciBoard) {
                return ToolResult::error('CI_BOARD_NOT_FOUND', 'Das angegebene CI Board wurde nicht gefunden.');
            }

            // Policy prüfen
            if (!Gate::forUser($context->user)->allows('view', $ciBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses CI Board.');
            }
            
            // Query aufbauen
            $query = BrandsCiBoardColor::query()
                ->where('brand_ci_board_id', $ciBoardId)
                ->with('ciBoard');

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'title', 'color', 'description', 'created_at', 'updated_at'
            ]);
            
            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            
            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'title', 'order', 'created_at', 'updated_at'
            ], 'order', 'asc');
            
            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            $colors = $query->get();

            $colorsList = $colors->map(function($color) {
                return [
                    'id' => $color->id,
                    'uuid' => $color->uuid,
                    'title' => $color->title,
                    'color' => $color->color,
                    'description' => $color->description,
                    'order' => $color->order,
                    'ci_board_id' => $color->brand_ci_board_id,
                    'created_at' => $color->created_at->toIso8601String(),
                    'updated_at' => $color->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'colors' => $colorsList,
                'count' => count($colorsList),
                'ci_board_id' => $ciBoardId,
                'ci_board_name' => $ciBoard->name,
                'message' => count($colorsList) > 0 
                    ? count($colorsList) . ' Farbe(n) gefunden für CI Board "' . $ciBoard->name . '".'
                    : 'Keine Farben gefunden für CI Board "' . $ciBoard->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Farben: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'ci_board_color', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
