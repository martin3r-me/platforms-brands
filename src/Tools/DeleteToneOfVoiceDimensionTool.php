<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsToneOfVoiceDimension;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteToneOfVoiceDimensionTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.tone_of_voice_dimensions.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/tone_of_voice_dimensions/{id} - Löscht eine Tone-Dimension. REST-Parameter: dimension_id (required, integer) - Dimensions-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'dimension_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Tone-Dimension (ERFORDERLICH).'
                ],
            ],
            'required' => ['dimension_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'dimension_id',
                BrandsToneOfVoiceDimension::class,
                'DIMENSION_NOT_FOUND',
                'Die angegebene Tone-Dimension wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $dimension = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('delete', $dimension);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Tone-Dimension nicht löschen.');
            }

            $dimensionName = $dimension->name;
            $dimensionId = $dimension->id;
            $boardId = $dimension->tone_of_voice_board_id;

            $dimension->delete();

            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('brands.tone_of_voice_dimensions.GET', $context->user->id, $context->team?->id);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'dimension_id' => $dimensionId,
                'dimension_name' => $dimensionName,
                'tone_of_voice_board_id' => $boardId,
                'message' => "Tone-Dimension '{$dimensionName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Tone-Dimension: ' . $e->getMessage());
        }
    }
}
