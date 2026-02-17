<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsMoodboardBoard;
use Platform\Brands\Models\BrandsMoodboardImage;
use Illuminate\Support\Facades\Gate;

class ListMoodboardImagesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.moodboard_images.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/moodboard_boards/{moodboard_board_id}/images - Listet Bilder eines Moodboards auf. REST-Parameter: moodboard_board_id (required, integer). filters (optional). sort (optional). limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'moodboard_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Moodboards.'
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

            $boardId = $arguments['moodboard_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'moodboard_board_id ist erforderlich.');
            }

            $board = BrandsMoodboardBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('MOODBOARD_BOARD_NOT_FOUND', 'Das angegebene Moodboard wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Moodboard.');
            }

            $query = BrandsMoodboardImage::query()
                ->where('moodboard_board_id', $boardId);

            $this->applyStandardFilters($query, $arguments, [
                'title', 'type', 'created_at', 'updated_at'
            ]);
            $this->applyStandardSearch($query, $arguments, ['title', 'annotation']);
            $this->applyStandardSort($query, $arguments, [
                'title', 'type', 'created_at', 'updated_at', 'order'
            ], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $images = $query->get();

            $imagesList = $images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'uuid' => $image->uuid,
                    'title' => $image->title,
                    'image_path' => $image->image_path,
                    'annotation' => $image->annotation,
                    'tags' => $image->tags,
                    'type' => $image->type,
                    'order' => $image->order,
                    'created_at' => $image->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'moodboard_images' => $imagesList,
                'count' => count($imagesList),
                'moodboard_board_id' => $boardId,
                'moodboard_board_name' => $board->name,
                'message' => count($imagesList) > 0
                    ? count($imagesList) . ' Bild(er) im Moodboard "' . $board->name . '" gefunden.'
                    : 'Keine Bilder im Moodboard "' . $board->name . '" gefunden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Moodboard-Bilder: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'moodboard_image', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
