<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateContentBriefBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'brands.content_brief_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /brands/content_brief_boards/{id} - Aktualisiert ein Content Brief Board. REST-Parameter: content_brief_board_id (required, integer). name, description, content_type, search_intent, status, target_slug, target_word_count, seo_board_id, done (alle optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Brief Boards (ERFORDERLICH).'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Arbeitstitel / H1-Kandidat.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Zusammenfassung des Artikelziels.'
                ],
                'content_type' => [
                    'type' => 'string',
                    'description' => 'Content-Typ. Erlaubte Werte per Lookup-Tabelle (brands.lookup_values.GET name="content_type").'
                ],
                'search_intent' => [
                    'type' => 'string',
                    'description' => 'Such-Intent. Erlaubte Werte per Lookup-Tabelle (brands.lookup_values.GET name="search_intent").'
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Status des Briefs. Erlaubte Werte per Lookup-Tabelle (brands.lookup_values.GET name="content_brief_status").'
                ],
                'target_slug' => [
                    'type' => 'string',
                    'description' => 'Geplante URL / Slug.'
                ],
                'target_url' => [
                    'type' => 'string',
                    'description' => 'Qualifizierte Ziel-URL (z.B. https://example.com/guide/keyword).'
                ],
                'target_word_count' => [
                    'type' => 'integer',
                    'description' => 'Zielwortanzahl.'
                ],
                'seo_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des verknüpften SEO Boards.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Als erledigt markieren.'
                ],
            ],
            'required' => ['content_brief_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments, $context, 'content_brief_board_id', BrandsContentBriefBoard::class,
                'CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief Board wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $board = $validation['model'];

            try {
                Gate::forUser($context->user)->authorize('update', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst dieses Content Brief Board nicht bearbeiten (Policy).');
            }

            // Validate gegen Lookup-Tabellen (mit Fallback auf Konstanten)
            $teamId = $board->team_id;

            if (isset($arguments['content_type']) && !BrandsContentBriefBoard::isValidLookupValue('content_type', $arguments['content_type'], $teamId)) {
                $allowed = BrandsContentBriefBoard::getAllowedValues('content_type', $teamId);
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger content_type. Erlaubt: ' . implode(', ', $allowed));
            }

            if (isset($arguments['search_intent']) && !BrandsContentBriefBoard::isValidLookupValue('search_intent', $arguments['search_intent'], $teamId)) {
                $allowed = BrandsContentBriefBoard::getAllowedValues('search_intent', $teamId);
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger search_intent. Erlaubt: ' . implode(', ', $allowed));
            }

            if (isset($arguments['status']) && !BrandsContentBriefBoard::isValidLookupValue('content_brief_status', $arguments['status'], $teamId)) {
                $allowed = BrandsContentBriefBoard::getAllowedValues('content_brief_status', $teamId);
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger status. Erlaubt: ' . implode(', ', $allowed));
            }

            $updateData = [];

            foreach (['name', 'description', 'content_type', 'search_intent', 'status', 'target_slug', 'target_url', 'target_word_count', 'seo_board_id'] as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                $updateData['done_at'] = $arguments['done'] ? now() : null;
            }

            if (!empty($updateData)) {
                $board->update($updateData);
            }

            $board->refresh();
            $board->load(['brand', 'user', 'team', 'seoBoard']);

            return ToolResult::success([
                'content_brief_board_id' => $board->id,
                'content_brief_board_name' => $board->name,
                'description' => $board->description,
                'content_type' => $board->content_type,
                'search_intent' => $board->search_intent,
                'status' => $board->status,
                'target_slug' => $board->target_slug,
                'target_url' => $board->target_url,
                'target_word_count' => $board->target_word_count,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'seo_board_id' => $board->seo_board_id,
                'team_id' => $board->team_id,
                'done' => $board->done,
                'done_at' => $board->done_at?->toIso8601String(),
                'updated_at' => $board->updated_at->toIso8601String(),
                'message' => "Content Brief Board '{$board->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Content Brief Boards: ' . $e->getMessage());
        }
    }
}
