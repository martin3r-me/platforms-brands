<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsPersonaBoard;
use Platform\Brands\Models\BrandsPersona;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreatePersonaTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.personas.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/persona_boards/{persona_board_id}/personas - Erstellt eine neue Persona/Zielgruppe. REST-Parameter: persona_board_id (required), name (required), age, gender, occupation, location, education, income_range, bio, pain_points (array), goals (array), quotes (array), behaviors (array), channels (array), brands_liked (array), tone_of_voice_board_id (optional, Verknüpfung zu Tone of Voice).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'persona_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Persona Boards (ERFORDERLICH). Nutze "brands.persona_boards.GET" um Boards zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Persona (ERFORDERLICH), z.B. "Marketing-Maria", "Tech-Thomas".'
                ],
                'age' => [
                    'type' => 'integer',
                    'description' => 'Alter der Persona.'
                ],
                'gender' => [
                    'type' => 'string',
                    'description' => 'Geschlecht der Persona.',
                    'enum' => ['female', 'male', 'diverse']
                ],
                'occupation' => [
                    'type' => 'string',
                    'description' => 'Beruf/Position der Persona.'
                ],
                'location' => [
                    'type' => 'string',
                    'description' => 'Wohnort der Persona.'
                ],
                'education' => [
                    'type' => 'string',
                    'description' => 'Bildungsstand der Persona.'
                ],
                'income_range' => [
                    'type' => 'string',
                    'description' => 'Einkommensbereich, z.B. "40.000-60.000 EUR".'
                ],
                'bio' => [
                    'type' => 'string',
                    'description' => 'Kurze Biografie/Beschreibung der Persona.'
                ],
                'pain_points' => [
                    'type' => 'array',
                    'description' => 'Pain Points der Persona als Array von Objekten [{text: string}].',
                    'items' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']]]
                ],
                'goals' => [
                    'type' => 'array',
                    'description' => 'Ziele der Persona als Array von Objekten [{text: string}].',
                    'items' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']]]
                ],
                'quotes' => [
                    'type' => 'array',
                    'description' => 'Typische Zitate der Persona als Array von Objekten [{text: string}].',
                    'items' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']]]
                ],
                'behaviors' => [
                    'type' => 'array',
                    'description' => 'Verhalten/Gewohnheiten als Array von Objekten [{text: string}].',
                    'items' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']]]
                ],
                'channels' => [
                    'type' => 'array',
                    'description' => 'Bevorzugte Kommunikationskanäle als Array von Objekten [{text: string}].',
                    'items' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']]]
                ],
                'brands_liked' => [
                    'type' => 'array',
                    'description' => 'Lieblingsmarken der Persona als Array von Objekten [{text: string}].',
                    'items' => ['type' => 'object', 'properties' => ['text' => ['type' => 'string']]]
                ],
                'tone_of_voice_board_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des verknüpften Tone of Voice Boards. Definiert welcher Ton für diese Zielgruppe verwendet wird.'
                ],
            ],
            'required' => ['persona_board_id', 'name']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $boardId = $arguments['persona_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'persona_board_id ist erforderlich.');
            }

            $board = BrandsPersonaBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('PERSONA_BOARD_NOT_FOUND', 'Das angegebene Persona Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Personas für dieses Board erstellen.');
            }

            $name = $arguments['name'] ?? null;
            if (!$name) {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $persona = BrandsPersona::create([
                'persona_board_id' => $board->id,
                'name' => $name,
                'age' => $arguments['age'] ?? null,
                'gender' => $arguments['gender'] ?? null,
                'occupation' => $arguments['occupation'] ?? null,
                'location' => $arguments['location'] ?? null,
                'education' => $arguments['education'] ?? null,
                'income_range' => $arguments['income_range'] ?? null,
                'bio' => $arguments['bio'] ?? null,
                'pain_points' => $arguments['pain_points'] ?? null,
                'goals' => $arguments['goals'] ?? null,
                'quotes' => $arguments['quotes'] ?? null,
                'behaviors' => $arguments['behaviors'] ?? null,
                'channels' => $arguments['channels'] ?? null,
                'brands_liked' => $arguments['brands_liked'] ?? null,
                'tone_of_voice_board_id' => $arguments['tone_of_voice_board_id'] ?? null,
            ]);

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
                'created_at' => $persona->created_at->toIso8601String(),
                'message' => "Persona '{$persona->name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Persona: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'persona', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
