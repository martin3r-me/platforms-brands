<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefNote;

class ListContentBriefNotesTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_notes.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_brief_notes - Listet Briefing-Notizen eines Content Briefs auf, gruppiert nach note_type und sortiert nach order. REST-Parameter: content_brief_id (required, integer) - Content Brief Board-ID. note_type (optional, string) - Filtert auf einen bestimmten Typ (instruction, source, constraint, example, avoid).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Brief Boards (ERFORDERLICH).'
                ],
                'note_type' => [
                    'type' => 'string',
                    'enum' => ['instruction', 'source', 'constraint', 'example', 'avoid'],
                    'description' => 'Optional: Filtert auf einen bestimmten Notiz-Typ.'
                ],
            ],
            'required' => ['content_brief_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $contentBriefId = $arguments['content_brief_id'] ?? null;
            if (!$contentBriefId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_brief_id ist erforderlich.');
            }

            $board = BrandsContentBriefBoard::find($contentBriefId);
            if (!$board) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief Board wurde nicht gefunden.');
            }

            $noteType = $arguments['note_type'] ?? null;
            if ($noteType && !array_key_exists($noteType, BrandsContentBriefNote::NOTE_TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger note_type. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefNote::NOTE_TYPES)));
            }

            $query = BrandsContentBriefNote::where('content_brief_id', $contentBriefId);

            if ($noteType) {
                $query->where('note_type', $noteType);
            }

            $notes = $query->orderBy('note_type', 'asc')->orderBy('order', 'asc')->get();

            $notesList = $notes->map(function ($note) {
                return [
                    'id' => $note->id,
                    'content_brief_id' => $note->content_brief_id,
                    'note_type' => $note->note_type,
                    'note_type_label' => BrandsContentBriefNote::NOTE_TYPES[$note->note_type] ?? $note->note_type,
                    'content' => $note->content,
                    'order' => $note->order,
                    'created_at' => $note->created_at->toIso8601String(),
                    'updated_at' => $note->updated_at->toIso8601String(),
                ];
            })->values()->toArray();

            // Group by note_type
            $grouped = [];
            foreach (array_keys(BrandsContentBriefNote::NOTE_TYPES) as $type) {
                $typeNotes = array_values(array_filter($notesList, fn($n) => $n['note_type'] === $type));
                if (!empty($typeNotes)) {
                    $grouped[$type] = [
                        'label' => BrandsContentBriefNote::NOTE_TYPES[$type],
                        'notes' => $typeNotes,
                        'count' => count($typeNotes),
                    ];
                }
            }

            return ToolResult::success([
                'notes' => $notesList,
                'grouped' => $grouped,
                'count' => count($notesList),
                'content_brief_id' => $contentBriefId,
                'content_brief_name' => $board->name,
                'message' => count($notesList) > 0
                    ? count($notesList) . ' Note(s) für Content Brief "' . $board->name . '" gefunden.'
                    : 'Keine Notes für Content Brief "' . $board->name . '" gefunden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Content Brief Notes: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_brief_note', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
