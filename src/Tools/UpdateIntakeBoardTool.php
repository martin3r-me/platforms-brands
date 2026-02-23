<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsIntakeBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Bearbeiten von IntakeBoards
 */
class UpdateIntakeBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.intake_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/intake_boards/{id} - Aktualisiert ein Intake Board (Erhebung). REST-Parameter: intake_board_id (required, integer) - Intake Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. ai_personality (optional, string) - KI-Persönlichkeit. industry_context (optional, string) - Branchenkontext. ai_instructions (optional, array) - KI-Anweisungen. order (optional, integer) - Reihenfolge. done (optional, boolean) - Als erledigt markieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'intake_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des IntakeBoards (ERFORDERLICH). Nutze "brands.intake_boards.GET" um Intake Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des Intake Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Intake Boards.'
                ],
                'ai_personality' => [
                    'type' => 'string',
                    'description' => 'Optional: KI-Persönlichkeit für das Intake Board.'
                ],
                'industry_context' => [
                    'type' => 'string',
                    'description' => 'Optional: Branchenkontext für das Intake Board.'
                ],
                'ai_instructions' => [
                    'type' => 'array',
                    'description' => 'Optional: KI-Anweisungen für das Intake Board (Array von Strings).'
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Reihenfolge des Intake Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Intake Board als erledigt markieren.'
                ],
            ],
            'required' => ['intake_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'intake_board_id',
                BrandsIntakeBoard::class,
                'INTAKE_BOARD_NOT_FOUND',
                'Das angegebene Intake Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $intakeBoard = $validation['model'];

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $intakeBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Intake Board nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            if (isset($arguments['ai_personality'])) {
                $updateData['ai_personality'] = $arguments['ai_personality'];
            }

            if (isset($arguments['industry_context'])) {
                $updateData['industry_context'] = $arguments['industry_context'];
            }

            if (isset($arguments['ai_instructions'])) {
                $updateData['ai_instructions'] = $arguments['ai_instructions'];
            }

            if (isset($arguments['order'])) {
                $updateData['order'] = $arguments['order'];
            }

            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                if ($arguments['done']) {
                    $updateData['done_at'] = now();
                } else {
                    $updateData['done_at'] = null;
                }
            }

            // IntakeBoard aktualisieren
            if (!empty($updateData)) {
                $intakeBoard->update($updateData);
            }

            $intakeBoard->refresh();
            $intakeBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'intake_board_id' => $intakeBoard->id,
                'intake_board_name' => $intakeBoard->name,
                'description' => $intakeBoard->description,
                'ai_personality' => $intakeBoard->ai_personality,
                'industry_context' => $intakeBoard->industry_context,
                'ai_instructions' => $intakeBoard->ai_instructions,
                'status' => $intakeBoard->status,
                'is_active' => $intakeBoard->is_active,
                'brand_id' => $intakeBoard->brand_id,
                'brand_name' => $intakeBoard->brand->name,
                'team_id' => $intakeBoard->team_id,
                'done' => $intakeBoard->done,
                'done_at' => $intakeBoard->done_at?->toIso8601String(),
                'updated_at' => $intakeBoard->updated_at->toIso8601String(),
                'message' => "Intake Board '{$intakeBoard->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Intake Boards: ' . $e->getMessage());
        }
    }
}
