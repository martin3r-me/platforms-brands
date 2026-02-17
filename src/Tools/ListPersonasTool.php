<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsPersonaBoard;
use Platform\Brands\Models\BrandsPersona;
use Illuminate\Support\Facades\Gate;

class ListPersonasTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.personas.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/persona_boards/{persona_board_id}/personas - Listet Personas eines Boards auf. REST-Parameter: persona_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'persona_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Persona Boards. Nutze "brands.persona_boards.GET" um Boards zu finden.'
                    ],
                ]
            ]
        );
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

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Board.');
            }

            $query = BrandsPersona::query()
                ->where('persona_board_id', $boardId)
                ->with(['personaBoard', 'toneOfVoiceBoard']);

            $this->applyStandardFilters($query, $arguments, [
                'name', 'gender', 'occupation', 'location', 'created_at', 'updated_at'
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'bio', 'occupation', 'location']);
            $this->applyStandardSort($query, $arguments, [
                'name', 'age', 'order', 'created_at', 'updated_at'
            ], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $personas = $query->get();

            $personasList = $personas->map(function ($persona) {
                return [
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
                    'created_at' => $persona->created_at->toIso8601String(),
                    'updated_at' => $persona->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'personas' => $personasList,
                'count' => count($personasList),
                'persona_board_id' => $boardId,
                'persona_board_name' => $board->name,
                'message' => count($personasList) > 0
                    ? count($personasList) . ' Persona(s) gefunden für Board "' . $board->name . '".'
                    : 'Keine Personas gefunden für Board "' . $board->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Personas: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'persona', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
