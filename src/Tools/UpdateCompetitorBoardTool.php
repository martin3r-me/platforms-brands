<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateCompetitorBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.competitor_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/competitor_boards/{id} - Aktualisiert ein Wettbewerber Board. REST-Parameter: competitor_board_id (required, integer) - Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. done (optional, boolean) - Als erledigt markieren. axis_x_label, axis_y_label, axis_x_min_label, axis_x_max_label, axis_y_min_label, axis_y_max_label (optional, string) - Achsen-Labels.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'competitor_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Wettbewerber Boards (ERFORDERLICH). Nutze "brands.competitor_boards.GET" um Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des Wettbewerber Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des Wettbewerber Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Board als erledigt markieren.'
                ],
                'axis_x_label' => [
                    'type' => 'string',
                    'description' => 'Optional: Label der X-Achse.'
                ],
                'axis_y_label' => [
                    'type' => 'string',
                    'description' => 'Optional: Label der Y-Achse.'
                ],
                'axis_x_min_label' => [
                    'type' => 'string',
                    'description' => 'Optional: Label für das Minimum der X-Achse.'
                ],
                'axis_x_max_label' => [
                    'type' => 'string',
                    'description' => 'Optional: Label für das Maximum der X-Achse.'
                ],
                'axis_y_min_label' => [
                    'type' => 'string',
                    'description' => 'Optional: Label für das Minimum der Y-Achse.'
                ],
                'axis_y_max_label' => [
                    'type' => 'string',
                    'description' => 'Optional: Label für das Maximum der Y-Achse.'
                ],
            ],
            'required' => ['competitor_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'competitor_board_id',
                BrandsCompetitorBoard::class,
                'COMPETITOR_BOARD_NOT_FOUND',
                'Das angegebene Wettbewerber Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $board = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Wettbewerber Board nicht bearbeiten (Policy).');
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
            if (isset($arguments['axis_x_label'])) {
                $updateData['axis_x_label'] = $arguments['axis_x_label'];
            }
            if (isset($arguments['axis_y_label'])) {
                $updateData['axis_y_label'] = $arguments['axis_y_label'];
            }
            if (isset($arguments['axis_x_min_label'])) {
                $updateData['axis_x_min_label'] = $arguments['axis_x_min_label'];
            }
            if (isset($arguments['axis_x_max_label'])) {
                $updateData['axis_x_max_label'] = $arguments['axis_x_max_label'];
            }
            if (isset($arguments['axis_y_min_label'])) {
                $updateData['axis_y_min_label'] = $arguments['axis_y_min_label'];
            }
            if (isset($arguments['axis_y_max_label'])) {
                $updateData['axis_y_max_label'] = $arguments['axis_y_max_label'];
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
                'axis_x_label' => $board->axis_x_label,
                'axis_y_label' => $board->axis_y_label,
                'axis_x_min_label' => $board->axis_x_min_label,
                'axis_x_max_label' => $board->axis_x_max_label,
                'axis_y_min_label' => $board->axis_y_min_label,
                'axis_y_max_label' => $board->axis_y_max_label,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'done' => $board->done,
                'done_at' => $board->done_at?->toIso8601String(),
                'updated_at' => $board->updated_at->toIso8601String(),
                'message' => "Wettbewerber Board '{$board->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Wettbewerber Boards: ' . $e->getMessage());
        }
    }
}
