<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsAssetBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateAssetBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.asset_boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/{brand_id}/asset_boards - Erstellt ein neues Asset Board für eine Marke. Zentrale Ablage für Templates, Vorlagen und Brand Assets. REST-Parameter: brand_id (required, integer) - Marken-ID. name (optional, string) - Board-Name. description (optional, string) - Beschreibung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Marke, zu der das Asset Board gehört (ERFORDERLICH). Nutze "brands.brands.GET" um Marken zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Asset Boards. Wenn nicht angegeben, wird automatisch "Neues Asset Board" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des Asset Boards.'
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

            $name = $arguments['name'] ?? 'Neues Asset Board';

            $board = BrandsAssetBoard::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
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
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'team_id' => $board->team_id,
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Asset Board '{$board->name}' erfolgreich für Marke '{$board->brand->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Asset Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'asset_board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
