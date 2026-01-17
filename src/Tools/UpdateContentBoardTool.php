<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Bearbeiten von ContentBoards
 */
class UpdateContentBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/content_boards/{id} - Aktualisiert ein Content Board. REST-Parameter: content_board_id (required, integer) - Content Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. done (optional, boolean) - Als erledigt markieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des ContentBoards (ERFORDERLICH). Nutze "brands.content_boards.GET" um Content Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des Content Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Content Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Content Board als erledigt markieren.'
                ],
            ],
            'required' => ['content_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'content_board_id',
                BrandsContentBoard::class,
                'CONTENT_BOARD_NOT_FOUND',
                'Das angegebene Content Board wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $contentBoard = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $contentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Content Board nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                if ($arguments['done']) {
                    $updateData['done_at'] = now();
                } else {
                    $updateData['done_at'] = null;
                }
            }

            // ContentBoard aktualisieren
            if (!empty($updateData)) {
                $contentBoard->update($updateData);
            }

            $contentBoard->refresh();
            $contentBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'content_board_id' => $contentBoard->id,
                'content_board_name' => $contentBoard->name,
                'description' => $contentBoard->description,
                'brand_id' => $contentBoard->brand_id,
                'brand_name' => $contentBoard->brand->name,
                'team_id' => $contentBoard->team_id,
                'done' => $contentBoard->done,
                'done_at' => $contentBoard->done_at?->toIso8601String(),
                'updated_at' => $contentBoard->updated_at->toIso8601String(),
                'message' => "Content Board '{$contentBoard->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Content Boards: ' . $e->getMessage());
        }
    }
}
