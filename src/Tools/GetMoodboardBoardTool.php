<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsMoodboardBoard;
use Illuminate\Support\Facades\Gate;

class GetMoodboardBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.moodboard_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/moodboard_boards/{id} - Gibt ein einzelnes Moodboard zurück inkl. aller Bilder mit Tags und Annotationen. REST-Parameter: moodboard_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'moodboard_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Moodboards (ERFORDERLICH). Nutze "brands.moodboard_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['moodboard_board_id']
        ];
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

            $board = BrandsMoodboardBoard::with(['brand', 'images', 'user', 'team'])->find($boardId);
            if (!$board) {
                return ToolResult::error('MOODBOARD_BOARD_NOT_FOUND', 'Das angegebene Moodboard wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Moodboard.');
            }

            $images = $board->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'uuid' => $image->uuid,
                    'title' => $image->title,
                    'image_path' => $image->image_path,
                    'annotation' => $image->annotation,
                    'tags' => $image->tags,
                    'type' => $image->type,
                    'order' => $image->order,
                ];
            })->toArray();

            $doImages = $board->images->where('type', 'do')->count();
            $dontImages = $board->images->where('type', 'dont')->count();

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'team_id' => $board->team_id,
                'done' => $board->done,
                'images' => $images,
                'images_count' => count($images),
                'do_images_count' => $doImages,
                'dont_images_count' => $dontImages,
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Moodboard '{$board->name}' mit " . count($images) . " Bildern geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Moodboards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'moodboard_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
