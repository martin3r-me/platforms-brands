<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsIntakeBoardBlock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Bearbeiten von IntakeBoardBlocks
 */
class UpdateIntakeBoardBlockTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.intake_board_blocks.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/intake_board_blocks/{id} - Aktualisiert einen Intake Board Block. REST-Parameter: board_block_id (required, integer) - Intake Board Block-ID. sort_order (optional, integer) - Sortierung. is_required (optional, boolean) - Pflichtfeld. is_active (optional, boolean) - Aktiv.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'board_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Intake Board Blocks (ERFORDERLICH). Nutze "brands.intake_board_blocks.GET" um Intake Board Blocks zu finden.'
                ],
                'sort_order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Sortierposition des Blocks im Board.'
                ],
                'is_required' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob der Block ein Pflichtfeld ist.'
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob der Block aktiv ist.'
                ],
            ],
            'required' => ['board_block_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'board_block_id',
                BrandsIntakeBoardBlock::class,
                'INTAKE_BOARD_BLOCK_NOT_FOUND',
                'Der angegebene Intake Board Block wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $boardBlock = $validation['model'];
            $boardBlock->load('intakeBoard');

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $boardBlock);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Intake Board Block nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['sort_order'])) {
                $updateData['sort_order'] = $arguments['sort_order'];
            }

            if (isset($arguments['is_required'])) {
                $updateData['is_required'] = $arguments['is_required'];
            }

            if (isset($arguments['is_active'])) {
                $updateData['is_active'] = $arguments['is_active'];
            }

            // IntakeBoardBlock aktualisieren
            if (!empty($updateData)) {
                $boardBlock->update($updateData);
            }

            $boardBlock->refresh();
            $boardBlock->load(['intakeBoard', 'blockDefinition']);

            return ToolResult::success([
                'board_block_id' => $boardBlock->id,
                'intake_board_id' => $boardBlock->intake_board_id,
                'intake_board_name' => $boardBlock->intakeBoard->name,
                'block_definition_id' => $boardBlock->block_definition_id,
                'block_definition_name' => $boardBlock->blockDefinition->name ?? null,
                'sort_order' => $boardBlock->sort_order,
                'is_required' => $boardBlock->is_required,
                'is_active' => $boardBlock->is_active,
                'updated_at' => $boardBlock->updated_at->toIso8601String(),
                'message' => "Intake Board Block erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Intake Board Blocks: ' . $e->getMessage());
        }
    }
}
