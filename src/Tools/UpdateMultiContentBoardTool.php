<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsMultiContentBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Bearbeiten von MultiContentBoards
 */
class UpdateMultiContentBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.multi_content_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/multi_content_boards/{id} - Aktualisiert ein Multi-Content-Board. REST-Parameter: multi_content_board_id (required, integer) - Multi-Content-Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. done (optional, boolean) - Als erledigt markieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'multi_content_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Multi-Content-Boards (ERFORDERLICH). Nutze "brands.multi_content_boards.GET" um Multi-Content-Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des Multi-Content-Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Multi-Content-Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Multi-Content-Board als erledigt markieren.'
                ],
            ],
            'required' => ['multi_content_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'multi_content_board_id',
                BrandsMultiContentBoard::class,
                'MULTI_CONTENT_BOARD_NOT_FOUND',
                'Das angegebene Multi-Content-Board wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $multiContentBoard = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $multiContentBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Multi-Content-Board nicht bearbeiten (Policy).');
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

            // MultiContentBoard aktualisieren
            if (!empty($updateData)) {
                $multiContentBoard->update($updateData);
            }

            $multiContentBoard->refresh();
            $multiContentBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'multi_content_board_id' => $multiContentBoard->id,
                'multi_content_board_name' => $multiContentBoard->name,
                'description' => $multiContentBoard->description,
                'brand_id' => $multiContentBoard->brand_id,
                'brand_name' => $multiContentBoard->brand->name,
                'team_id' => $multiContentBoard->team_id,
                'done' => $multiContentBoard->done,
                'done_at' => $multiContentBoard->done_at?->toIso8601String(),
                'updated_at' => $multiContentBoard->updated_at->toIso8601String(),
                'message' => "Multi-Content-Board '{$multiContentBoard->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Multi-Content-Boards: ' . $e->getMessage());
        }
    }
}
