<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsIntakeBoard;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von IntakeBoards im Brands-Modul
 */
class ListIntakeBoardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.intake_boards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/{brand_id}/intake_boards - Listet Intake Boards (Erhebungen) einer Marke auf. REST-Parameter: brand_id (required, integer) - Marken-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'brand_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID der Marke. Nutze "brands.brands.GET" um Marken zu finden.'
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

            $brandId = $arguments['brand_id'] ?? null;
            if (!$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'brand_id ist erforderlich.');
            }

            $brand = BrandsBrand::find($brandId);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Marke wurde nicht gefunden.');
            }

            // Policy prüfen
            if (!Gate::forUser($context->user)->allows('view', $brand)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Marke.');
            }

            // Query aufbauen - Intake Boards
            $query = BrandsIntakeBoard::query()
                ->where('brand_id', $brandId)
                ->with(['brand', 'user', 'team']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'status', 'done', 'is_active', 'created_at', 'updated_at'
            ]);

            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);

            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'name', 'created_at', 'updated_at', 'order', 'status'
            ], 'order', 'asc');

            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            // Boards holen und per Policy filtern
            $boards = $query->get()->filter(function ($board) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $board);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            // Boards formatieren
            $boardsList = $boards->map(function($intakeBoard) {
                return [
                    'id' => $intakeBoard->id,
                    'uuid' => $intakeBoard->uuid,
                    'name' => $intakeBoard->name,
                    'description' => $intakeBoard->description,
                    'status' => $intakeBoard->status,
                    'is_active' => $intakeBoard->is_active,
                    'public_token' => $intakeBoard->public_token,
                    'public_url' => $intakeBoard->getPublicUrl(),
                    'brand_id' => $intakeBoard->brand_id,
                    'brand_name' => $intakeBoard->brand->name,
                    'team_id' => $intakeBoard->team_id,
                    'user_id' => $intakeBoard->user_id,
                    'done' => $intakeBoard->done,
                    'done_at' => $intakeBoard->done_at?->toIso8601String(),
                    'started_at' => $intakeBoard->started_at?->toIso8601String(),
                    'completed_at' => $intakeBoard->completed_at?->toIso8601String(),
                    'created_at' => $intakeBoard->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'intake_boards' => $boardsList,
                'count' => count($boardsList),
                'brand_id' => $brandId,
                'brand_name' => $brand->name,
                'message' => count($boardsList) > 0
                    ? count($boardsList) . ' Intake Board(s) gefunden für Marke "' . $brand->name . '".'
                    : 'Keine Intake Boards gefunden für Marke "' . $brand->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Intake Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake_board', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
