<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCiBoardColor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateCiBoardColorTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.ci_board_colors.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/ci_board_colors/{id} - Aktualisiert eine Farbe. REST-Parameter: color_id (required, integer) - Farb-ID. title (optional, string) - Titel. color (optional, string) - Hex-Farbwert. description (optional, string) - Beschreibung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'color_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Farbe (ERFORDERLICH). Nutze "brands.ci_board_colors.GET" um Farben zu finden.'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Titel der Farbe.'
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'Optional: Hex-Farbwert (z.B. "#FF0000").'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung der Farbe.'
                ],
            ],
            'required' => ['color_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'color_id',
                BrandsCiBoardColor::class,
                'COLOR_NOT_FOUND',
                'Die angegebene Farbe wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $color = $validation['model'];
            
            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('update', $color);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Farbe nicht bearbeiten.');
            }

            $updateData = [];

            if (isset($arguments['title'])) {
                $updateData['title'] = $arguments['title'];
            }

            if (isset($arguments['color'])) {
                // Farbwert normalisieren
                $colorValue = ltrim($arguments['color'], '#');
                if (strlen($colorValue) === 3) {
                    $colorValue = $colorValue[0] . $colorValue[0] . $colorValue[1] . $colorValue[1] . $colorValue[2] . $colorValue[2];
                }
                $updateData['color'] = '#' . $colorValue;
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            if (!empty($updateData)) {
                $color->update($updateData);
            }

            $color->refresh();
            $color->load('ciBoard');

            return ToolResult::success([
                'id' => $color->id,
                'uuid' => $color->uuid,
                'title' => $color->title,
                'color' => $color->color,
                'description' => $color->description,
                'order' => $color->order,
                'ci_board_id' => $color->brand_ci_board_id,
                'updated_at' => $color->updated_at->toIso8601String(),
                'message' => "Farbe '{$color->title}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Farbe: ' . $e->getMessage());
        }
    }
}
