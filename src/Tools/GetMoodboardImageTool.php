<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsMoodboardImage;
use Illuminate\Support\Facades\Gate;

class GetMoodboardImageTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.moodboard_image.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/moodboard_images/{id} - Gibt ein einzelnes Moodboard-Bild zurück. REST-Parameter: moodboard_image_id (required, integer) - Bild-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'moodboard_image_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Moodboard-Bildes (ERFORDERLICH).'
                ],
            ],
            'required' => ['moodboard_image_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $imageId = $arguments['moodboard_image_id'] ?? null;
            if (!$imageId) {
                return ToolResult::error('VALIDATION_ERROR', 'moodboard_image_id ist erforderlich.');
            }

            $image = BrandsMoodboardImage::with(['moodboardBoard.brand'])->find($imageId);
            if (!$image) {
                return ToolResult::error('MOODBOARD_IMAGE_NOT_FOUND', 'Das angegebene Moodboard-Bild wurde nicht gefunden.');
            }

            $board = $image->moodboardBoard;
            if (!$board || !Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Moodboard-Bild.');
            }

            return ToolResult::success([
                'id' => $image->id,
                'uuid' => $image->uuid,
                'title' => $image->title,
                'image_path' => $image->image_path,
                'annotation' => $image->annotation,
                'tags' => $image->tags,
                'type' => $image->type,
                'order' => $image->order,
                'moodboard_board_id' => $image->moodboard_board_id,
                'moodboard_board_name' => $board->name,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name ?? null,
                'created_at' => $image->created_at->toIso8601String(),
                'message' => "Moodboard-Bild geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Moodboard-Bildes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'moodboard_image', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
