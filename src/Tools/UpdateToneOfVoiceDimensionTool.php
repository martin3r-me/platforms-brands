<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsToneOfVoiceDimension;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateToneOfVoiceDimensionTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.tone_of_voice_dimensions.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/tone_of_voice_dimensions/{id} - Aktualisiert eine Tone-Dimension. REST-Parameter: dimension_id (required), name, label_left, label_right, value (0-100), description (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'dimension_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Tone-Dimension (ERFORDERLICH).'
                ],
                'name' => ['type' => 'string', 'description' => 'Optional: Name der Dimension.'],
                'label_left' => ['type' => 'string', 'description' => 'Optional: Linkes Label.'],
                'label_right' => ['type' => 'string', 'description' => 'Optional: Rechtes Label.'],
                'value' => ['type' => 'integer', 'description' => 'Optional: Position (0-100).'],
                'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
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
                Gate::forUser($context->user)->authorize('update', $dimension);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Tone-Dimension nicht bearbeiten.');
            }

            $fields = ['name', 'label_left', 'label_right', 'description'];
            $updateData = [];

            foreach ($fields as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (isset($arguments['value'])) {
                $updateData['value'] = max(0, min(100, (int) $arguments['value']));
            }

            if (!empty($updateData)) {
                $dimension->update($updateData);
            }

            $dimension->refresh();
            $dimension->load('toneOfVoiceBoard');

            return ToolResult::success([
                'id' => $dimension->id,
                'uuid' => $dimension->uuid,
                'name' => $dimension->name,
                'label_left' => $dimension->label_left,
                'label_right' => $dimension->label_right,
                'value' => $dimension->value,
                'description' => $dimension->description,
                'order' => $dimension->order,
                'tone_of_voice_board_id' => $dimension->tone_of_voice_board_id,
                'updated_at' => $dimension->updated_at->toIso8601String(),
                'message' => "Tone-Dimension '{$dimension->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Tone-Dimension: ' . $e->getMessage());
        }
    }
}
