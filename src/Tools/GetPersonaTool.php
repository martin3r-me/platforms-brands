<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsPersona;
use Illuminate\Support\Facades\Gate;

class GetPersonaTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.persona.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/personas/{id} - Gibt eine einzelne Persona zurück. REST-Parameter: persona_id (required, integer) - Persona-ID.';
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
            ],
            'required' => ['persona_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $personaId = $arguments['persona_id'] ?? null;
            if (!$personaId) {
                return ToolResult::error('VALIDATION_ERROR', 'persona_id ist erforderlich.');
            }

            $persona = BrandsPersona::with(['personaBoard', 'toneOfVoiceBoard'])->find($personaId);
            if (!$persona) {
                return ToolResult::error('PERSONA_NOT_FOUND', 'Die angegebene Persona wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $persona)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Persona.');
            }

            return ToolResult::success([
                'id' => $persona->id,
                'uuid' => $persona->uuid,
                'name' => $persona->name,
                'avatar_url' => $persona->avatar_url,
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
                'persona_board_name' => $persona->personaBoard->name,
                'created_at' => $persona->created_at->toIso8601String(),
                'updated_at' => $persona->updated_at->toIso8601String(),
                'message' => "Persona '{$persona->name}' geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Persona: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'persona', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
