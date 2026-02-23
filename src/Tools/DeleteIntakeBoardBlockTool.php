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
 * Tool zum Löschen von IntakeBoardBlocks im Brands-Modul
 */
class DeleteIntakeBoardBlockTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.intake_board_blocks.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/intake_board_blocks/{id} - Löscht einen Intake Board Block. REST-Parameter: board_block_id (required, integer) - Intake Board Block-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'board_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Intake Board Blocks (ERFORDERLICH). Nutze "brands.intake_board_blocks.GET" um Intake Board Blocks zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass der Intake Board Block wirklich gelöscht werden soll.'
                ]
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
            $board = $boardBlock->intakeBoard;

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('delete', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Intake Board Block nicht löschen (Policy).');
            }

            $boardBlockId = $boardBlock->id;
            $intakeBoardId = $board->id;
            $teamId = $boardBlock->team_id;

            // IntakeBoardBlock löschen
            $boardBlock->delete();

            // Cache invalidieren
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.intake_board_blocks.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'board_block_id' => $boardBlockId,
                'intake_board_id' => $intakeBoardId,
                'message' => "Intake Board Block wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Intake Board Blocks: ' . $e->getMessage());
        }
    }
}
