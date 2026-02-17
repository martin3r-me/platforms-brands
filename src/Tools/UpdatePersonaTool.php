<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsPersona;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdatePersonaTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.personas.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/personas/{id} - Aktualisiert eine Persona. REST-Parameter: persona_id (required), name, age, gender, occupation, location, education, income_range, bio, pain_points, goals, quotes, behaviors, channels, brands_liked, tone_of_voice_board_id (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'persona_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Persona (ERFORDERLICH). Nutze "brands.personas.GET" um Personas zu finden.'
                ],
                'name' => ['type' => 'string', 'description' => 'Optional: Name der Persona.'],
                'age' => ['type' => 'integer', 'description' => 'Optional: Alter.'],
                'gender' => ['type' => 'string', 'description' => 'Optional: Geschlecht.', 'enum' => ['female', 'male', 'diverse']],
                'occupation' => ['type' => 'string', 'description' => 'Optional: Beruf.'],
                'location' => ['type' => 'string', 'description' => 'Optional: Wohnort.'],
                'education' => ['type' => 'string', 'description' => 'Optional: Bildung.'],
                'income_range' => ['type' => 'string', 'description' => 'Optional: Einkommensbereich.'],
                'bio' => ['type' => 'string', 'description' => 'Optional: Biografie.'],
                'pain_points' => ['type' => 'array', 'description' => 'Optional: Pain Points [{text: string}].'],
                'goals' => ['type' => 'array', 'description' => 'Optional: Ziele [{text: string}].'],
                'quotes' => ['type' => 'array', 'description' => 'Optional: Zitate [{text: string}].'],
                'behaviors' => ['type' => 'array', 'description' => 'Optional: Verhalten [{text: string}].'],
                'channels' => ['type' => 'array', 'description' => 'Optional: Kanäle [{text: string}].'],
                'brands_liked' => ['type' => 'array', 'description' => 'Optional: Lieblingsmarken [{text: string}].'],
                'tone_of_voice_board_id' => ['type' => 'integer', 'description' => 'Optional: Verknüpftes Tone of Voice Board. Auf null setzen zum Entfernen.'],
            ],
            'required' => ['persona_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'persona_id',
                BrandsPersona::class,
                'PERSONA_NOT_FOUND',
                'Die angegebene Persona wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $persona = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $persona);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Persona nicht bearbeiten.');
            }

            $fields = ['name', 'age', 'gender', 'occupation', 'location', 'education', 'income_range', 'bio', 'pain_points', 'goals', 'quotes', 'behaviors', 'channels', 'brands_liked', 'tone_of_voice_board_id'];
            $updateData = [];

            foreach ($fields as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $persona->update($updateData);
            }

            $persona->refresh();
            $persona->load(['personaBoard', 'toneOfVoiceBoard']);

            return ToolResult::success([
                'id' => $persona->id,
                'uuid' => $persona->uuid,
                'name' => $persona->name,
                'age' => $persona->age,
                'gender' => $persona->gender,
                'gender_label' => $persona->gender_label,
                'occupation' => $persona->occupation,
                'location' => $persona->location,
                'education' => $persona->education,
                'income_range' => $persona->income_range,
                'bio' => $persona->bio,
                'pain_points' => $persona->pain_points,
                'goals' => $persona->goals,
                'quotes' => $persona->quotes,
                'behaviors' => $persona->behaviors,
                'channels' => $persona->channels,
                'brands_liked' => $persona->brands_liked,
                'tone_of_voice_board_id' => $persona->tone_of_voice_board_id,
                'tone_of_voice_board_name' => $persona->toneOfVoiceBoard?->name,
                'order' => $persona->order,
                'persona_board_id' => $persona->persona_board_id,
                'updated_at' => $persona->updated_at->toIso8601String(),
                'message' => "Persona '{$persona->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Persona: ' . $e->getMessage());
        }
    }
}
