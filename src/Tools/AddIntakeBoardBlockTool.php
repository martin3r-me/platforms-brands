<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsIntakeBoard;
use Platform\Brands\Models\BrandsIntakeBoardBlock;
use Platform\Brands\Models\BrandsIntakeBlockDefinition;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Hinzufügen eines Blocks zu einem Intake Board (Junction: Board <-> BlockDefinition)
 */
class AddIntakeBoardBlockTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.intake_board_blocks.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/intake_boards/{intake_board_id}/intake_board_blocks - Fügt einen Block (BlockDefinition) zu einem Intake Board hinzu. REST-Parameter: intake_board_id (required, integer) - Intake Board-ID. block_definition_id (required, integer) - Block Definition-ID. sort_order (optional, integer) - Sortierung. is_required (optional, boolean) - Pflichtfeld. is_active (optional, boolean) - Aktiv.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'intake_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Intake Boards (ERFORDERLICH). Nutze "brands.intake_boards.GET" um Intake Boards zu finden.'
                ],
                'block_definition_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Block Definition (ERFORDERLICH). Nutze "brands.intake_block_definitions.GET" um Block Definitions zu finden.'
                ],
                'sort_order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Sortierposition des Blocks im Board.'
                ],
                'is_required' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob der Block ein Pflichtfeld ist. Standard: false.'
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob der Block aktiv ist. Standard: true.'
                ],
            ],
            'required' => ['intake_board_id', 'block_definition_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Intake Board finden
            $intakeBoardId = $arguments['intake_board_id'] ?? null;
            if (!$intakeBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'intake_board_id ist erforderlich.');
            }

            $board = BrandsIntakeBoard::find($intakeBoardId);
            if (!$board) {
                return ToolResult::error('INTAKE_BOARD_NOT_FOUND', 'Das angegebene Intake Board wurde nicht gefunden.');
            }

            // Block Definition finden
            $blockDefinitionId = $arguments['block_definition_id'] ?? null;
            if (!$blockDefinitionId) {
                return ToolResult::error('VALIDATION_ERROR', 'block_definition_id ist erforderlich.');
            }

            $blockDefinition = BrandsIntakeBlockDefinition::find($blockDefinitionId);
            if (!$blockDefinition) {
                return ToolResult::error('BLOCK_DEFINITION_NOT_FOUND', 'Die angegebene Block Definition wurde nicht gefunden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Blocks zu diesem Intake Board hinzufügen (Policy).');
            }

            // BrandsIntakeBoardBlock erstellen
            $boardBlock = BrandsIntakeBoardBlock::create([
                'intake_board_id' => $board->id,
                'block_definition_id' => $blockDefinition->id,
                'sort_order' => $arguments['sort_order'] ?? 0,
                'is_required' => $arguments['is_required'] ?? false,
                'is_active' => $arguments['is_active'] ?? true,
                'user_id' => $board->user_id,
                'team_id' => $board->team_id,
            ]);

            $boardBlock->load(['intakeBoard', 'blockDefinition']);

            return ToolResult::success([
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
                'created_at' => $boardBlock->created_at->toIso8601String(),
                'message' => "Intake Board Block erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Hinzufügen des Intake Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'intake_board_block', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
