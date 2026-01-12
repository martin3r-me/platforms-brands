<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCiBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Bearbeiten von CiBoards
 */
class UpdateCiBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.ci_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/ci_boards/{id} - Aktualisiert ein CI Board. REST-Parameter: ci_board_id (required, integer) - CI Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. primary_color (optional, string) - Primärfarbe (Hex-Code). secondary_color (optional, string) - Sekundärfarbe. accent_color (optional, string) - Akzentfarbe. slogan (optional, string) - Slogan. font_family (optional, string) - Schriftart. tagline (optional, string) - Tagline. done (optional, boolean) - Als erledigt markieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ci_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des CiBoards (ERFORDERLICH). Nutze "brands.ci_boards.GET" um CI Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des CI Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des CI Boards.'
                ],
                'primary_color' => [
                    'type' => 'string',
                    'description' => 'Optional: Primärfarbe als Hex-Code (z.B. "#FF0000").'
                ],
                'secondary_color' => [
                    'type' => 'string',
                    'description' => 'Optional: Sekundärfarbe als Hex-Code.'
                ],
                'accent_color' => [
                    'type' => 'string',
                    'description' => 'Optional: Akzentfarbe als Hex-Code.'
                ],
                'slogan' => [
                    'type' => 'string',
                    'description' => 'Optional: Slogan der Marke.'
                ],
                'font_family' => [
                    'type' => 'string',
                    'description' => 'Optional: Schriftart (z.B. "Arial", "Helvetica").'
                ],
                'tagline' => [
                    'type' => 'string',
                    'description' => 'Optional: Tagline der Marke.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: CI Board als erledigt markieren.'
                ],
            ],
            'required' => ['ci_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'ci_board_id',
                BrandsCiBoard::class,
                'CI_BOARD_NOT_FOUND',
                'Das angegebene CI Board wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $ciBoard = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $ciBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses CI Board nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            if (isset($arguments['primary_color'])) {
                $updateData['primary_color'] = $arguments['primary_color'];
            }

            if (isset($arguments['secondary_color'])) {
                $updateData['secondary_color'] = $arguments['secondary_color'];
            }

            if (isset($arguments['accent_color'])) {
                $updateData['accent_color'] = $arguments['accent_color'];
            }

            if (isset($arguments['slogan'])) {
                $updateData['slogan'] = $arguments['slogan'];
            }

            if (isset($arguments['font_family'])) {
                $updateData['font_family'] = $arguments['font_family'];
            }

            if (isset($arguments['tagline'])) {
                $updateData['tagline'] = $arguments['tagline'];
            }

            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                if ($arguments['done']) {
                    $updateData['done_at'] = now();
                } else {
                    $updateData['done_at'] = null;
                }
            }

            // CiBoard aktualisieren
            if (!empty($updateData)) {
                $ciBoard->update($updateData);
            }

            $ciBoard->refresh();
            $ciBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'ci_board_id' => $ciBoard->id,
                'ci_board_name' => $ciBoard->name,
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
                'done' => $ciBoard->done,
                'done_at' => $ciBoard->done_at?->toIso8601String(),
                'updated_at' => $ciBoard->updated_at->toIso8601String(),
                'message' => "CI Board '{$ciBoard->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des CI Boards: ' . $e->getMessage());
        }
    }
}
