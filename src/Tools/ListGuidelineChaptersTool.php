<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Brands\Models\BrandsGuidelineBoard;
use Platform\Brands\Models\BrandsGuidelineChapter;
use Illuminate\Support\Facades\Gate;

class ListGuidelineChaptersTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.guideline_chapters.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/guideline_boards/{guideline_board_id}/chapters - Listet Kapitel eines Guidelines Boards auf. REST-Parameter: guideline_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'guideline_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Guidelines Boards.'
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

            $boardId = $arguments['guideline_board_id'] ?? null;
            if (!$boardId) {
                return ToolResult::error('VALIDATION_ERROR', 'guideline_board_id ist erforderlich.');
            }

            $board = BrandsGuidelineBoard::find($boardId);
            if (!$board) {
                return ToolResult::error('GUIDELINE_BOARD_NOT_FOUND', 'Das angegebene Guidelines Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Board.');
            }

            $query = BrandsGuidelineChapter::query()
                ->where('guideline_board_id', $boardId)
                ->with(['entries', 'guidelineBoard']);

            $this->applyStandardFilters($query, $arguments, ['title', 'created_at', 'updated_at']);
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, ['title', 'order', 'created_at', 'updated_at'], 'order', 'asc');
            $this->applyStandardPagination($query, $arguments);

            $chapters = $query->get();

            $chaptersList = $chapters->map(function ($chapter) {
                return [
                    'id' => $chapter->id,
                    'uuid' => $chapter->uuid,
                    'title' => $chapter->title,
                    'description' => $chapter->description,
                    'icon' => $chapter->icon,
                    'order' => $chapter->order,
                    'entries_count' => $chapter->entries->count(),
                    'guideline_board_id' => $chapter->guideline_board_id,
                    'created_at' => $chapter->created_at->toIso8601String(),
                    'updated_at' => $chapter->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'chapters' => $chaptersList,
                'count' => count($chaptersList),
                'guideline_board_id' => $boardId,
                'guideline_board_name' => $board->name,
                'message' => count($chaptersList) > 0
                    ? count($chaptersList) . ' Kapitel gefunden für Board "' . $board->name . '".'
                    : 'Keine Kapitel gefunden für Board "' . $board->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Kapitel: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'guideline_chapter', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
