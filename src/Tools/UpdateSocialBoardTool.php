<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsSocialBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Bearbeiten von SocialBoards
 */
class UpdateSocialBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.social_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/social_boards/{id} - Aktualisiert ein Social Board. REST-Parameter: social_board_id (required, integer) - Social Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. done (optional, boolean) - Als erledigt markieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'social_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SocialBoards (ERFORDERLICH). Nutze "brands.social_boards.GET" um Social Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des Social Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Social Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Social Board als erledigt markieren.'
                ],
            ],
            'required' => ['social_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'social_board_id',
                BrandsSocialBoard::class,
                'SOCIAL_BOARD_NOT_FOUND',
                'Das angegebene Social Board wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $socialBoard = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $socialBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Social Board nicht bearbeiten (Policy).');
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

            // SocialBoard aktualisieren
            if (!empty($updateData)) {
                $socialBoard->update($updateData);
            }

            $socialBoard->refresh();
            $socialBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'social_board_id' => $socialBoard->id,
                'social_board_name' => $socialBoard->name,
                'description' => $socialBoard->description,
                'brand_id' => $socialBoard->brand_id,
                'brand_name' => $socialBoard->brand->name,
                'team_id' => $socialBoard->team_id,
                'done' => $socialBoard->done,
                'done_at' => $socialBoard->done_at?->toIso8601String(),
                'updated_at' => $socialBoard->updated_at->toIso8601String(),
                'message' => "Social Board '{$socialBoard->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Social Boards: ' . $e->getMessage());
        }
    }
}
