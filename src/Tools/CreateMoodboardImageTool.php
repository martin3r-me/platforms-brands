<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsMoodboardBoard;
use Platform\Brands\Models\BrandsMoodboardImage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateMoodboardImageTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.moodboard_images.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/moodboard_boards/{moodboard_board_id}/images - Erstellt einen neuen Moodboard-Bild-Eintrag. REST-Parameter: moodboard_board_id (required), context_file_id (required, ID eines bereits hochgeladenen ContextFiles), title (optional), annotation (optional), tags (optional, array), type (optional, "do"|"dont").';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'moodboard_board_id' => ['type' => 'integer', 'description' => 'ID des Moodboards (ERFORDERLICH).'],
                'context_file_id' => ['type' => 'integer', 'description' => 'ID eines bereits hochgeladenen ContextFiles (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Titel des Bildes.'],
                'annotation' => ['type' => 'string', 'description' => 'Annotation: Warum passt dieses Bild zur Marke (oder nicht).'],
                'tags' => [
                    'type' => 'array',
                    'description' => 'Kategorien/Tags für das Bild, z.B. ["Produkt", "Lifestyle", "People", "Texture"].',
                    'items' => ['type' => 'string'],
                ],
                'type' => [
                    'type' => 'string',
                    'description' => 'Typ: "do" (passend, Standard) oder "dont" (unpassend).',
                    'enum' => ['do', 'dont'],
                ],
            ],
            'required' => ['moodboard_board_id', 'context_file_id']
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

            $board = BrandsMoodboardBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('MOODBOARD_BOARD_NOT_FOUND', 'Das angegebene Moodboard wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Bilder für dieses Moodboard erstellen.');
            }

            $contextFileId = $arguments['context_file_id'] ?? null;
            if (!$contextFileId) {
                return ToolResult::error('VALIDATION_ERROR', 'context_file_id ist erforderlich.');
            }

            $contextFile = \Platform\Core\Models\ContextFile::find($contextFileId);
            if (!$contextFile) {
                return ToolResult::error('CONTEXT_FILE_NOT_FOUND', 'Das angegebene ContextFile wurde nicht gefunden.');
            }

            $image = BrandsMoodboardImage::create([
                'moodboard_board_id' => $board->id,
                'title' => $arguments['title'] ?? null,
                'annotation' => $arguments['annotation'] ?? null,
                'tags' => $arguments['tags'] ?? null,
                'type' => $arguments['type'] ?? 'do',
            ]);

            $image->addFileReference($contextFileId);
            $image->load('moodboardBoard');

            return ToolResult::success([
                'id' => $image->id,
                'uuid' => $image->uuid,
                'title' => $image->title,
                'image_url' => $image->image_url,
                'annotation' => $image->annotation,
                'tags' => $image->tags,
                'type' => $image->type,
                'order' => $image->order,
                'moodboard_board_id' => $image->moodboard_board_id,
                'context_file_id' => $contextFileId,
                'created_at' => $image->created_at->toIso8601String(),
                'message' => "Moodboard-Bild erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Moodboard-Bildes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'moodboard_image', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
