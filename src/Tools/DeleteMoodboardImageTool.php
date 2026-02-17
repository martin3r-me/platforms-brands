<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsMoodboardImage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteMoodboardImageTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.moodboard_images.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /brands/moodboard_images/{id} - Löscht ein Moodboard-Bild. REST-Parameter: moodboard_image_id (required, integer) - Bild-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'moodboard_image_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Moodboard-Bildes (ERFORDERLICH).'
                ],
            ],
            'required' => ['moodboard_image_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'moodboard_image_id',
                BrandsMoodboardImage::class, 'MOODBOARD_IMAGE_NOT_FOUND',
                'Das angegebene Moodboard-Bild wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $image = $validation['model'];
            $board = $image->moodboardBoard;

            if (!$board) {
                return ToolResult::error('BOARD_NOT_FOUND', 'Das zugehörige Moodboard wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Moodboard-Bild nicht löschen.');
            }

            $imageId = $image->id;
            $imageTitle = $image->title;
            $boardId = $board->id;

            $image->delete();

            return ToolResult::success([
                'moodboard_image_id' => $imageId,
                'moodboard_image_title' => $imageTitle,
                'moodboard_board_id' => $boardId,
                'message' => "Moodboard-Bild wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Moodboard-Bildes: ' . $e->getMessage());
        }
    }
}
