<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsBrand;
use Platform\Brands\Models\BrandsCiBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen von CiBoards im Brands-Modul
 */
class CreateCiBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.ci_boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/{brand_id}/ci_boards - Erstellt ein neues CI Board für eine Marke. REST-Parameter: brand_id (required, integer) - Marken-ID. name (optional, string) - Board-Name. description (optional, string) - Beschreibung. primary_color (optional, string) - Primärfarbe (Hex-Code). secondary_color (optional, string) - Sekundärfarbe. accent_color (optional, string) - Akzentfarbe. slogan (optional, string) - Slogan. font_family (optional, string) - Schriftart. tagline (optional, string) - Tagline.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Marke, zu der das CI Board gehört (ERFORDERLICH). Nutze "brands.brands.GET" um Marken zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des CI Boards. Wenn nicht angegeben, wird automatisch "Neues CI Board" verwendet.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Beschreibung des CI Boards.'
                ],
                'primary_color' => [
                    'type' => 'string',
                    'description' => 'Primärfarbe als Hex-Code (z.B. "#FF0000").'
                ],
                'secondary_color' => [
                    'type' => 'string',
                    'description' => 'Sekundärfarbe als Hex-Code.'
                ],
                'accent_color' => [
                    'type' => 'string',
                    'description' => 'Akzentfarbe als Hex-Code.'
                ],
                'slogan' => [
                    'type' => 'string',
                    'description' => 'Slogan der Marke.'
                ],
                'font_family' => [
                    'type' => 'string',
                    'description' => 'Schriftart (z.B. "Arial", "Helvetica").'
                ],
                'tagline' => [
                    'type' => 'string',
                    'description' => 'Tagline der Marke.'
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

            // Brand finden
            $brandId = $arguments['brand_id'] ?? null;
            if (!$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'brand_id ist erforderlich.');
            }

            $brand = BrandsBrand::find($brandId);
            if (!$brand) {
                return ToolResult::error('BRAND_NOT_FOUND', 'Die angegebene Marke wurde nicht gefunden. Nutze "brands.brands.GET" um Marken zu finden.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $brand);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Boards für diese Marke erstellen (Policy).');
            }

            $name = $arguments['name'] ?? 'Neues CI Board';

            // CiBoard direkt erstellen
            $ciBoard = BrandsCiBoard::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $brand->team_id,
                'brand_id' => $brand->id,
                'primary_color' => $arguments['primary_color'] ?? null,
                'secondary_color' => $arguments['secondary_color'] ?? null,
                'accent_color' => $arguments['accent_color'] ?? null,
                'slogan' => $arguments['slogan'] ?? null,
                'font_family' => $arguments['font_family'] ?? null,
                'tagline' => $arguments['tagline'] ?? null,
            ]);

            $ciBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'id' => $ciBoard->id,
                'uuid' => $ciBoard->uuid,
                'name' => $ciBoard->name,
                'description' => $ciBoard->description,
                'brand_id' => $ciBoard->brand_id,
                'brand_name' => $ciBoard->brand->name,
                'team_id' => $ciBoard->team_id,
                'primary_color' => $ciBoard->primary_color,
                'secondary_color' => $ciBoard->secondary_color,
                'accent_color' => $ciBoard->accent_color,
                'slogan' => $ciBoard->slogan,
                'font_family' => $ciBoard->font_family,
                'tagline' => $ciBoard->tagline,
                'created_at' => $ciBoard->created_at->toIso8601String(),
                'message' => "CI Board '{$ciBoard->name}' erfolgreich für Marke '{$ciBoard->brand->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des CI Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'ci_board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
