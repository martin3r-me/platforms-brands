<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Platform\Brands\Models\BrandsToneOfVoiceEntry;
use Illuminate\Support\Facades\Gate;

class ListToneOfVoiceEntriesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.tone_of_voice_entries.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/tone_of_voice_boards/{tone_of_voice_board_id}/entries - Listet Messaging-Einträge eines Boards auf. REST-Parameter: tone_of_voice_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'tone_of_voice_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Tone of Voice Boards. Nutze "brands.tone_of_voice_boards.GET" um Boards zu finden.'
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

            $boardId = $arguments['tone_of_voice_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'tone_of_voice_board_id ist erforderlich.');
            }

            $board = BrandsToneOfVoiceBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('TONE_OF_VOICE_BOARD_NOT_FOUND', 'Das angegebene Tone of Voice Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Board.');
            }

            $query = BrandsToneOfVoiceEntry::query()
                ->where('tone_of_voice_board_id', $boardId)
                ->with('toneOfVoiceBoard');

            $this->applyStandardFilters($query, $arguments, [
                'name', 'type', 'created_at', 'updated_at'
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'content', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'name', 'type', 'order', 'created_at', 'updated_at'
            ], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $entries = $query->get();

            $entriesList = $entries->map(function ($entry) {
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
                    'tone_of_voice_board_id' => $entry->tone_of_voice_board_id,
                    'created_at' => $entry->created_at->toIso8601String(),
                    'updated_at' => $entry->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'entries' => $entriesList,
                'count' => count($entriesList),
                'tone_of_voice_board_id' => $boardId,
                'tone_of_voice_board_name' => $board->name,
                'message' => count($entriesList) > 0
                    ? count($entriesList) . ' Messaging-Eintrag/-Einträge gefunden für Board "' . $board->name . '".'
                    : 'Keine Messaging-Einträge gefunden für Board "' . $board->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Messaging-Einträge: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'tone_of_voice_entry', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
