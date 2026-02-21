<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsCtaBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateCtaBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.cta_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/cta_boards/{id} - Aktualisiert ein CTA Board. REST-Parameter: cta_board_id (required, integer) - CTA Board-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. done (optional, boolean) - Als erledigt markieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cta_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des CTA Boards (ERFORDERLICH). Nutze "brands.cta_boards.GET" um CTA Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name des CTA Boards.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung des CTA Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: CTA Board als erledigt markieren.'
                ],
            ],
            'required' => ['cta_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'cta_board_id', BrandsCtaBoard::class,
                'CTA_BOARD_NOT_FOUND', 'Das angegebene CTA Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $ctaBoard = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $ctaBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses CTA Board nicht bearbeiten (Policy).');
            }

            $updateData = [];

            foreach (['name', 'description'] as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                $updateData['done_at'] = $arguments['done'] ? now() : null;
            }

            if (!empty($updateData)) {
                $ctaBoard->update($updateData);
            }

            $ctaBoard->refresh();
            $ctaBoard->load(['brand', 'user', 'team']);

            return ToolResult::success([
                'cta_board_id' => $ctaBoard->id,
                'cta_board_name' => $ctaBoard->name,
                'description' => $ctaBoard->description,
                'brand_id' => $ctaBoard->brand_id,
                'brand_name' => $ctaBoard->brand->name,
                'team_id' => $ctaBoard->team_id,
                'done' => $ctaBoard->done,
                'done_at' => $ctaBoard->done_at?->toIso8601String(),
                'updated_at' => $ctaBoard->updated_at->toIso8601String(),
                'message' => "CTA Board '{$ctaBoard->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des CTA Boards: ' . $e->getMessage());
        }
    }
}
