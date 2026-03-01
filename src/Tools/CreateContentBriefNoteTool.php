<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefNote;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateContentBriefNoteTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_notes.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_brief_notes - Erstellt eine neue Briefing-Notiz (Anweisung, Quelle, Einschränkung, Beispiel, Vermeidung) für einen Content Brief. REST-Parameter: content_brief_id (required, integer) - Content Brief Board-ID. note_type (required, string) - instruction|source|constraint|example|avoid. content (required, string) - Freitext-Inhalt der Notiz. order (optional, integer) - Sortierung.';
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
                    'description' => 'Typ der Notiz: instruction (Anweisung), source (Quelle), constraint (Einschränkung), example (Beispiel), avoid (Vermeiden). ERFORDERLICH.'
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Freitext-Inhalt der Notiz (ERFORDERLICH).'
                ],
                'order' => [
                    'type' => 'integer',
                    'description' => 'Sortierung. Wenn nicht angegeben, wird automatisch ans Ende sortiert.'
                ],
            ],
            'required' => ['content_brief_id', 'note_type', 'content']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $contentBriefId = $arguments['content_brief_id'] ?? null;
            $noteType = $arguments['note_type'] ?? null;
            $content = $arguments['content'] ?? null;

            if (!$contentBriefId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_brief_id ist erforderlich.');
            }
            if (!$noteType) {
                return ToolResult::error('VALIDATION_ERROR', 'note_type ist erforderlich.');
            }
            if (!$content) {
                return ToolResult::error('VALIDATION_ERROR', 'content ist erforderlich.');
            }

            // Validate note_type enum
            if (!array_key_exists($noteType, BrandsContentBriefNote::NOTE_TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger note_type. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefNote::NOTE_TYPES)));
            }

            // Check content brief exists
            $board = BrandsContentBriefBoard::find($contentBriefId);
            if (!$board) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief Board wurde nicht gefunden.');
            }

            // Authorization: user must be able to update the content brief
            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Notes für dieses Content Brief erstellen (Policy).');
            }

            // Auto-calculate order if not provided
            $order = $arguments['order'] ?? null;
            if ($order === null) {
                $maxOrder = BrandsContentBriefNote::where('content_brief_id', $contentBriefId)->max('order') ?? 0;
                $order = $maxOrder + 1;
            }

            $note = BrandsContentBriefNote::create([
                'content_brief_id' => $contentBriefId,
                'note_type' => $noteType,
                'content' => $content,
                'order' => $order,
                'user_id' => $context->user->id,
                'team_id' => $board->team_id,
            ]);

            $note->load('contentBrief');

            return ToolResult::success([
                'id' => $note->id,
                'content_brief_id' => $note->content_brief_id,
                'content_brief_name' => $note->contentBrief->name,
                'note_type' => $note->note_type,
                'note_type_label' => BrandsContentBriefNote::NOTE_TYPES[$note->note_type] ?? $note->note_type,
                'content' => $note->content,
                'order' => $note->order,
                'created_at' => $note->created_at->toIso8601String(),
                'message' => "Note ('{$note->note_type}') erfolgreich für Content Brief '{$note->contentBrief->name}' erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Content Brief Note: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_brief_note', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
