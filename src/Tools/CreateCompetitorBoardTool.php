<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCompetitorBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateCompetitorBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.competitor_boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/{brand_id}/competitor_boards - Erstellt ein neues Wettbewerber Board für eine Marke. REST-Parameter: brand_id (required, integer) - Marken-ID. name (optional, string) - Board-Name. description (optional, string) - Beschreibung. axis_x_label (optional, string) - X-Achsen-Label. axis_y_label (optional, string) - Y-Achsen-Label.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Marke, zu der das Wettbewerber Board gehört (ERFORDERLICH). Nutze "brands.brands.GET" um Marken zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Wettbewerber Boards. Wenn nicht angegeben, wird automatisch "Neues Wettbewerber Board" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des Wettbewerber Boards.'
                ],
                'axis_x_label' => [
                    'type' => 'string',
                    'description' => 'Optional: Label der X-Achse. Standard: "Preis".'
                ],
                'axis_y_label' => [
                    'type' => 'string',
                    'description' => 'Optional: Label der Y-Achse. Standard: "Qualität".'
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
            'required' => ['brand_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $brandId = $arguments['brand_id'] ?? null;
            if (!$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'brand_id ist erforderlich.');
            }

            $brand = BrandsBrand::find($brandId);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Marke wurde nicht gefunden. Nutze "brands.brands.GET" um Marken zu finden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Boards für diese Marke erstellen (Policy).');
            }

            $name = $arguments['name'] ?? 'Neues Wettbewerber Board';

            $board = BrandsCompetitorBoard::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'axis_x_label' => $arguments['axis_x_label'] ?? 'Preis',
                'axis_y_label' => $arguments['axis_y_label'] ?? 'Qualität',
                'axis_x_min_label' => $arguments['axis_x_min_label'] ?? null,
                'axis_x_max_label' => $arguments['axis_x_max_label'] ?? null,
                'axis_y_min_label' => $arguments['axis_y_min_label'] ?? null,
                'axis_y_max_label' => $arguments['axis_y_max_label'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $brand->team_id,
                'brand_id' => $brand->id,
            ]);

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
                'team_id' => $board->team_id,
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Wettbewerber Board '{$board->name}' erfolgreich für Marke '{$board->brand->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Wettbewerber Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'competitor_board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
