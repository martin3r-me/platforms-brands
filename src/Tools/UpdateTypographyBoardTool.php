<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsTypographyBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateTypographyBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.typography_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/typography_boards/{id} - Aktualisiert ein Typografie Board. REST-Parameter: typography_board_id (required, integer) - Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. done (optional, boolean) - Als erledigt markieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'typography_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Typografie Boards (ERFORDERLICH). Nutze "brands.typography_boards.GET" um Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des Typografie Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Typografie Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Board als erledigt markieren.'
                ],
            ],
            'required' => ['typography_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'typography_board_id',
                BrandsTypographyBoard::class,
                'TYPOGRAPHY_BOARD_NOT_FOUND',
                'Das angegebene Typografie Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $board = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Typografie Board nicht bearbeiten (Policy).');
            }

            $updateData = [];
            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }
            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }
            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                $updateData['done_at'] = $arguments['done'] ? now() : null;
            }

            if (!empty($updateData)) {
                $board->update($updateData);
            }

            $board->refresh();
            $board->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'done' => $board->done,
                'done_at' => $board->done_at?->toIso8601String(),
                'updated_at' => $board->updated_at->toIso8601String(),
                'message' => "Typografie Board '{$board->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Typografie Boards: ' . $e->getMessage());
        }
    }
}
