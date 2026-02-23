<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsIntakeBoard;
use Platform\Brands\Models\BrandsIntakeBoardBlock;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von IntakeBoardBlocks im Brands-Modul
 */
class ListIntakeBoardBlocksTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.intake_board_blocks.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/intake_boards/{intake_board_id}/intake_board_blocks - Listet Intake Board Blocks eines Intake Boards auf. REST-Parameter: intake_board_id (required, integer) - Intake Board-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'intake_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Intake Boards. Nutze "brands.intake_boards.GET" um Intake Boards zu finden.'
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

            $intakeBoardId = $arguments['intake_board_id'] ?? null;
            if (!$intakeBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'intake_board_id ist erforderlich.');
            }

            $board = BrandsIntakeBoard::find($intakeBoardId);
            if (!$board) {
                return ToolResult::error('INTAKE_BOARD_NOT_FOUND', 'Das angegebene Intake Board wurde nicht gefunden.');
            }

            // Policy prüfen
            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Intake Board.');
            }

            // Query aufbauen - Intake Board Blocks
            $query = BrandsIntakeBoardBlock::query()
                ->where('intake_board_id', $intakeBoardId)
                ->with(['intakeBoard', 'blockDefinition', 'user', 'team'])
                ->orderBy('sort_order', 'asc');

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'sort_order', 'is_required', 'is_active', 'created_at', 'updated_at'
            ]);

            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['sort_order']);

            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'sort_order', 'created_at', 'updated_at'
            ], 'sort_order', 'asc');

            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            // Blocks holen
            $blocks = $query->get();

            // Blocks formatieren
            $blocksList = $blocks->map(function($boardBlock) {
                return [
                    'id' => $boardBlock->id,
                    'uuid' => $boardBlock->uuid,
                    'intake_board_id' => $boardBlock->intake_board_id,
                    'intake_board_name' => $boardBlock->intakeBoard->name,
                    'block_definition_id' => $boardBlock->block_definition_id,
                    'block_definition_name' => $boardBlock->blockDefinition->name ?? null,
                    'sort_order' => $boardBlock->sort_order,
                    'is_required' => $boardBlock->is_required,
                    'is_active' => $boardBlock->is_active,
                    'team_id' => $boardBlock->team_id,
                    'user_id' => $boardBlock->user_id,
                    'created_at' => $boardBlock->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'intake_board_blocks' => $blocksList,
                'count' => count($blocksList),
                'intake_board_id' => $intakeBoardId,
                'intake_board_name' => $board->name,
                'message' => count($blocksList) > 0
                    ? count($blocksList) . ' Intake Board Block(s) gefunden für Intake Board "' . $board->name . '".'
                    : 'Keine Intake Board Blocks gefunden für Intake Board "' . $board->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Intake Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'intake_board_block', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
