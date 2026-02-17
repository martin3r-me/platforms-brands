<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsGuidelineBoard;
use Illuminate\Support\Facades\Gate;

class GetGuidelineBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.guideline_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/guideline_boards/{id} - Gibt ein einzelnes Guidelines Board zurück inkl. aller Kapitel und Einträge. REST-Parameter: guideline_board_id (required, integer) - Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'guideline_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Guidelines Boards (ERFORDERLICH). Nutze "brands.guideline_boards.GET" um Boards zu finden.'
                ],
            ],
            'required' => ['guideline_board_id']
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

            $board = BrandsGuidelineBoard::with(['brand', 'chapters.entries', 'user', 'team'])->find($boardId);
            if (!$board) {
                return ToolResult::error('GUIDELINE_BOARD_NOT_FOUND', 'Das angegebene Guidelines Board wurde nicht gefunden.');
            }

            if (!Gate::forUser($context->user)->allows('view', $board)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Guidelines Board.');
            }

            $chapters = $board->chapters->map(function ($chapter) {
                return [
                    'id' => $chapter->id,
                    'uuid' => $chapter->uuid,
                    'title' => $chapter->title,
                    'description' => $chapter->description,
                    'icon' => $chapter->icon,
                    'order' => $chapter->order,
                    'entries' => $chapter->entries->map(function ($entry) {
                        return [
                            'id' => $entry->id,
                            'uuid' => $entry->uuid,
                            'title' => $entry->title,
                            'rule_text' => $entry->rule_text,
                            'rationale' => $entry->rationale,
                            'do_example' => $entry->do_example,
                            'dont_example' => $entry->dont_example,
                            'cross_references' => $entry->cross_references,
                            'order' => $entry->order,
                        ];
                    })->toArray(),
                    'entries_count' => $chapter->entries->count(),
                ];
            })->toArray();

            $totalEntries = $board->chapters->sum(fn($ch) => $ch->entries->count());

            return ToolResult::success([
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'team_id' => $board->team_id,
                'done' => $board->done,
                'chapters' => $chapters,
                'chapters_count' => count($chapters),
                'entries_count' => $totalEntries,
                'created_at' => $board->created_at->toIso8601String(),
                'message' => "Guidelines Board '{$board->name}' mit " . count($chapters) . " Kapiteln und {$totalEntries} Regeln geladen."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Guidelines Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'guideline_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
