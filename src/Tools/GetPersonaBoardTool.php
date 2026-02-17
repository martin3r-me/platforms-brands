<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsPersonaBoard;
use Illuminate\Support\Facades\Gate;

class GetPersonaBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.persona_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/persona_boards/{id} - Gibt ein einzelnes Persona Board zurück inkl. aller Personas. REST-Parameter: persona_board_id (required, integer) - Board-ID.';
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
            ],
            'required' => ['persona_board_id']
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

            $board = BrandsPersonaBoard::with(['brand', 'personas', 'personas.toneOfVoiceBoard', 'user', 'team'])->find($boardId);
            if (!$board) {
                return ToolResult::error('PERSONA_BOARD_NOT_FOUND', 'Das angegebene Persona Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Persona Board.');
            }

            $personas = $board->personas->map(function ($persona) {
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
                ];
            })->toArray();

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'team_id' => $board->team_id,
                'done' => $board->done,
                'personas' => $personas,
                'personas_count' => count($personas),
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Persona Board '{$board->name}' mit " . count($personas) . " Persona(s) geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Persona Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'persona_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
