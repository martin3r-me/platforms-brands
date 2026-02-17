<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsMoodboardImage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateMoodboardImageTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.moodboard_images.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/moodboard_images/{id} - Aktualisiert ein Moodboard-Bild. REST-Parameter: moodboard_image_id (required), title (optional), annotation (optional), tags (optional, array), type (optional, "do"|"dont"), image_path (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'moodboard_image_id' => ['type' => 'integer', 'description' => 'ID des Moodboard-Bildes (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Optional: Titel des Bildes.'],
                'annotation' => ['type' => 'string', 'description' => 'Optional: Annotation (warum passt das Bild zur Marke).'],
                'tags' => [
                    'type' => 'array',
                    'description' => 'Optional: Kategorien/Tags.',
                    'items' => ['type' => 'string'],
                ],
                'type' => [
                    'type' => 'string',
                    'description' => 'Optional: "do" (passend) oder "dont" (unpassend).',
                    'enum' => ['do', 'dont'],
                ],
                'image_path' => ['type' => 'string', 'description' => 'Optional: Neuer Bildpfad.'],
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
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Moodboard-Bild nicht bearbeiten.');
            }

            $updateData = [];
            if (isset($arguments['title'])) $updateData['title'] = $arguments['title'];
            if (isset($arguments['annotation'])) $updateData['annotation'] = $arguments['annotation'];
            if (isset($arguments['tags'])) $updateData['tags'] = $arguments['tags'];
            if (isset($arguments['type'])) $updateData['type'] = $arguments['type'];
            if (isset($arguments['image_path'])) $updateData['image_path'] = $arguments['image_path'];

            if (!empty($updateData)) {
                $image->update($updateData);
            }

            $image->refresh();

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
                'updated_at' => $image->updated_at->toIso8601String(),
                'message' => "Moodboard-Bild erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Moodboard-Bildes: ' . $e->getMessage());
        }
    }
}
