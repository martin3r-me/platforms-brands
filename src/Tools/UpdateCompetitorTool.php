<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCompetitor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateCompetitorTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.competitors.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/competitors/{id} - Aktualisiert einen Wettbewerber. REST-Parameter: competitor_id (required), name, logo_url, website_url, description, strengths, weaknesses, notes, position_x, position_y, is_own_brand, differentiation (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'competitor_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Wettbewerbers (ERFORDERLICH). Nutze "brands.competitors.GET" um Wettbewerber zu finden.'
                ],
                'name' => ['type' => 'string', 'description' => 'Optional: Name des Wettbewerbers.'],
                'logo_url' => ['type' => 'string', 'description' => 'Optional: Logo-URL.'],
                'website_url' => ['type' => 'string', 'description' => 'Optional: Website-URL.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                'strengths' => ['type' => 'array', 'description' => 'Optional: Stärken [{text: string}].'],
                'weaknesses' => ['type' => 'array', 'description' => 'Optional: Schwächen [{text: string}].'],
                'notes' => ['type' => 'string', 'description' => 'Optional: Notizen.'],
                'position_x' => ['type' => 'integer', 'description' => 'Optional: X-Position auf der Positionierungsmatrix (0-100).'],
                'position_y' => ['type' => 'integer', 'description' => 'Optional: Y-Position auf der Positionierungsmatrix (0-100).'],
                'is_own_brand' => ['type' => 'boolean', 'description' => 'Optional: Eigene Marke markieren.'],
                'differentiation' => ['type' => 'array', 'description' => 'Optional: Differenzierungsmerkmale [{category: string, own_value: string, competitor_value: string}].'],
            ],
            'required' => ['competitor_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'competitor_id',
                BrandsCompetitor::class,
                'COMPETITOR_NOT_FOUND',
                'Der angegebene Wettbewerber wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $competitor = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $competitor);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Wettbewerber nicht bearbeiten.');
            }

            $fields = ['name', 'logo_url', 'website_url', 'description', 'strengths', 'weaknesses', 'notes', 'position_x', 'position_y', 'is_own_brand', 'differentiation'];
            $updateData = [];

            foreach ($fields as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $competitor->update($updateData);
            }

            $competitor->refresh();
            $competitor->load(['competitorBoard']);

            return ToolResult::success([
                'id' => $competitor->id,
                'uuid' => $competitor->uuid,
                'name' => $competitor->name,
                'logo_url' => $competitor->logo_url,
                'website_url' => $competitor->website_url,
                'description' => $competitor->description,
                'strengths' => $competitor->strengths,
                'weaknesses' => $competitor->weaknesses,
                'notes' => $competitor->notes,
                'position_x' => $competitor->position_x,
                'position_y' => $competitor->position_y,
                'is_own_brand' => $competitor->is_own_brand,
                'differentiation' => $competitor->differentiation,
                'order' => $competitor->order,
                'competitor_board_id' => $competitor->competitor_board_id,
                'updated_at' => $competitor->updated_at->toIso8601String(),
                'message' => "Wettbewerber '{$competitor->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Wettbewerbers: ' . $e->getMessage());
        }
    }
}
