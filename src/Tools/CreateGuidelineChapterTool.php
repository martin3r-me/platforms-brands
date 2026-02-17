<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsGuidelineBoard;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateGuidelineChapterTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.guideline_chapters.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/guideline_boards/{guideline_board_id}/chapters - Erstellt ein neues Kapitel im Guidelines Board. REST-Parameter: guideline_board_id (required), title (required), description (optional), icon (optional, z.B. "heroicon-o-paint-brush").';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'guideline_board_id' => ['type' => 'integer', 'description' => 'ID des Guidelines Boards (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Titel des Kapitels (ERFORDERLICH), z.B. "Logo-Verwendung", "Farbrichtlinien".'],
                'description' => ['type' => 'string', 'description' => 'Beschreibung des Kapitels.'],
                'icon' => ['type' => 'string', 'description' => 'Optional: Heroicon-Name, z.B. "heroicon-o-paint-brush".'],
            ],
            'required' => ['guideline_board_id', 'title']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $boardId = $arguments['guideline_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'guideline_board_id ist erforderlich.');
            }

            $board = BrandsGuidelineBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('GUIDELINE_BOARD_NOT_FOUND', 'Das angegebene Guidelines Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Kapitel für dieses Board erstellen.');
            }

            $title = $arguments['title'] ?? null;
            if (!$title) {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            $chapter = BrandsGuidelineChapter::create([
                'guideline_board_id' => $board->id,
                'title' => $title,
                'description' => $arguments['description'] ?? null,
                'icon' => $arguments['icon'] ?? null,
            ]);

            $chapter->load('guidelineBoard');

            return ToolResult::success([
                'id' => $chapter->id,
                'uuid' => $chapter->uuid,
                'title' => $chapter->title,
                'description' => $chapter->description,
                'icon' => $chapter->icon,
                'order' => $chapter->order,
                'guideline_board_id' => $chapter->guideline_board_id,
                'created_at' => $chapter->created_at->toIso8601String(),
                'message' => "Kapitel '{$chapter->title}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Kapitels: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'guideline_chapter', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
