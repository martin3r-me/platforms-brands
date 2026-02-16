<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Illuminate\Support\Facades\Gate;

class GetToneOfVoiceBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.tone_of_voice_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/tone_of_voice_boards/{id} - Gibt ein einzelnes Tone of Voice Board zurück inkl. aller Einträge und Dimensionen. REST-Parameter: tone_of_voice_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tone_of_voice_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Tone of Voice Boards (ERFORDERLICH). Nutze "brands.tone_of_voice_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['tone_of_voice_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $boardId = $arguments['tone_of_voice_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'tone_of_voice_board_id ist erforderlich.');
            }

            $board = BrandsToneOfVoiceBoard::with(['brand', 'entries', 'dimensions', 'user', 'team'])->find($boardId);
            if (!$board) {
                return ToolResult::error('TONE_OF_VOICE_BOARD_NOT_FOUND', 'Das angegebene Tone of Voice Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Tone of Voice Board.');
            }

            $entries = $board->entries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'uuid' => $entry->uuid,
                    'name' => $entry->name,
                    'type' => $entry->type,
                    'type_label' => $entry->type_label,
                    'content' => $entry->content,
                    'description' => $entry->description,
                    'example_positive' => $entry->example_positive,
                    'example_negative' => $entry->example_negative,
                    'order' => $entry->order,
                ];
            })->toArray();

            $dimensions = $board->dimensions->map(function ($dim) {
                return [
                    'id' => $dim->id,
                    'uuid' => $dim->uuid,
                    'name' => $dim->name,
                    'label_left' => $dim->label_left,
                    'label_right' => $dim->label_right,
                    'value' => $dim->value,
                    'description' => $dim->description,
                    'order' => $dim->order,
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
                'entries' => $entries,
                'entries_count' => count($entries),
                'dimensions' => $dimensions,
                'dimensions_count' => count($dimensions),
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Tone of Voice Board '{$board->name}' mit " . count($entries) . " Einträgen und " . count($dimensions) . " Dimensionen geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Tone of Voice Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'tone_of_voice_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
