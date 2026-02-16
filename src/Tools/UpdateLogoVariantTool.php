<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsLogoVariant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateLogoVariantTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.logo_variants.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/logo_variants/{id} - Aktualisiert eine Logo-Variante. REST-Parameter: variant_id (required), name, type, description, usage_guidelines, clearspace_factor, min_width_px, min_width_mm, background_color, dos, donts (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'variant_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Logo-Variante (ERFORDERLICH). Nutze "brands.logo_variants.GET" um Varianten zu finden.'
                ],
                'name' => ['type' => 'string', 'description' => 'Optional: Name der Logo-Variante.'],
                'type' => [
                    'type' => 'string',
                    'description' => 'Optional: Typ der Logo-Variante.',
                    'enum' => ['primary', 'secondary', 'monochrome', 'favicon', 'icon', 'wordmark', 'pictorial_mark', 'combination_mark']
                ],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                'usage_guidelines' => ['type' => 'string', 'description' => 'Optional: Verwendungsrichtlinien.'],
                'clearspace_factor' => ['type' => 'number', 'description' => 'Optional: Schutzzone (Faktor).'],
                'min_width_px' => ['type' => 'integer', 'description' => 'Optional: Mindestbreite (px).'],
                'min_width_mm' => ['type' => 'integer', 'description' => 'Optional: Mindestbreite (mm).'],
                'background_color' => ['type' => 'string', 'description' => 'Optional: Hintergrundfarbe (hex).'],
                'dos' => [
                    'type' => 'array',
                    'description' => 'Optional: Do\'s Liste.',
                    'items' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']]]
                ],
                'donts' => [
                    'type' => 'array',
                    'description' => 'Optional: Don\'ts Liste.',
                    'items' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']]]
                ],
            ],
            'required' => ['variant_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'variant_id',
                BrandsLogoVariant::class,
                'VARIANT_NOT_FOUND',
                'Die angegebene Logo-Variante wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $variant = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $variant);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Logo-Variante nicht bearbeiten.');
            }

            $fields = ['name', 'type', 'description', 'usage_guidelines', 'clearspace_factor', 'min_width_px', 'min_width_mm', 'background_color', 'dos', 'donts'];
            $updateData = [];

            foreach ($fields as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $variant->update($updateData);
            }

            $variant->refresh();
            $variant->load('logoBoard');

            return ToolResult::success([
                'id' => $variant->id,
                'uuid' => $variant->uuid,
                'name' => $variant->name,
                'type' => $variant->type,
                'type_label' => $variant->type_label,
                'description' => $variant->description,
                'usage_guidelines' => $variant->usage_guidelines,
                'clearspace_factor' => $variant->clearspace_factor,
                'min_width_px' => $variant->min_width_px,
                'min_width_mm' => $variant->min_width_mm,
                'background_color' => $variant->background_color,
                'dos' => $variant->dos,
                'donts' => $variant->donts,
                'order' => $variant->order,
                'logo_board_id' => $variant->logo_board_id,
                'updated_at' => $variant->updated_at->toIso8601String(),
                'message' => "Logo-Variante '{$variant->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Logo-Variante: ' . $e->getMessage());
        }
    }
}
