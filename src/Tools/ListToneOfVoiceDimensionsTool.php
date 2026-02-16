<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsToneOfVoiceBoard;
use Platform\Brands\Models\BrandsToneOfVoiceDimension;
use Illuminate\Support\Facades\Gate;

class ListToneOfVoiceDimensionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.tone_of_voice_dimensions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/tone_of_voice_boards/{tone_of_voice_board_id}/dimensions - Listet Tone-Dimensionen eines Boards auf. REST-Parameter: tone_of_voice_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'tone_of_voice_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Tone of Voice Boards.'
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

            $query = BrandsToneOfVoiceDimension::query()
                ->where('tone_of_voice_board_id', $boardId)
                ->with('toneOfVoiceBoard');

            $this->applyStandardFilters($query, $arguments, [
                'name', 'created_at', 'updated_at'
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'label_left', 'label_right', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'name', 'value', 'order', 'created_at', 'updated_at'
            ], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $dimensions = $query->get();

            $dimensionsList = $dimensions->map(function ($dim) {
                return [
                    'id' => $dim->id,
                    'uuid' => $dim->uuid,
                    'name' => $dim->name,
                    'label_left' => $dim->label_left,
                    'label_right' => $dim->label_right,
                    'value' => $dim->value,
                    'description' => $dim->description,
                    'order' => $dim->order,
                    'tone_of_voice_board_id' => $dim->tone_of_voice_board_id,
                    'created_at' => $dim->created_at->toIso8601String(),
                    'updated_at' => $dim->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'dimensions' => $dimensionsList,
                'count' => count($dimensionsList),
                'tone_of_voice_board_id' => $boardId,
                'tone_of_voice_board_name' => $board->name,
                'message' => count($dimensionsList) > 0
                    ? count($dimensionsList) . ' Tone-Dimension(en) gefunden für Board "' . $board->name . '".'
                    : 'Keine Tone-Dimensionen gefunden für Board "' . $board->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Tone-Dimensionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'tone_of_voice_dimension', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
