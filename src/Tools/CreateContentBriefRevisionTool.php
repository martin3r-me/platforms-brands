<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefRevision;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class CreateContentBriefRevisionTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_revisions.POST';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_brief_boards/{id}/revisions - Dokumentiert eine Content-Änderung (Revision) an einem veröffentlichten Brief. Hält fest: WAS geändert wurde (summary + changes), Metriken vorher/nachher (word_count, h2_count etc.), und den Revisionstyp. Dient der Korrelation mit Ranking-Entwicklung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Brief Boards (ERFORDERLICH).',
                ],
                'revision_type' => [
                    'type' => 'string',
                    'description' => 'Art der Änderung. Werte per Lookup-Tabelle (brands.lookup_values.GET name="revision_type"). z.B. optimization, rewrite, extension, structure_change, link_update, seo_fix, initial_publish.',
                ],
                'summary' => [
                    'type' => 'string',
                    'description' => 'ERFORDERLICH: Beschreibung der Änderung. z.B. "H2 \'POS-Vergleich\' hinzugefügt, Absatz zu Kassensoftware erweitert, 2 interne Links ergänzt".',
                ],
                'metrics_before' => [
                    'type' => 'object',
                    'description' => 'Metriken VOR der Änderung: {word_count, h2_count, h3_count, h4_count, paragraph_count, image_count, internal_link_count, external_link_count}.',
                    'properties' => [
                        'word_count' => ['type' => 'integer'],
                        'h2_count' => ['type' => 'integer'],
                        'h3_count' => ['type' => 'integer'],
                        'h4_count' => ['type' => 'integer'],
                        'paragraph_count' => ['type' => 'integer'],
                        'image_count' => ['type' => 'integer'],
                        'internal_link_count' => ['type' => 'integer'],
                        'external_link_count' => ['type' => 'integer'],
                    ],
                ],
                'metrics_after' => [
                    'type' => 'object',
                    'description' => 'Metriken NACH der Änderung (gleiche Struktur wie metrics_before).',
                    'properties' => [
                        'word_count' => ['type' => 'integer'],
                        'h2_count' => ['type' => 'integer'],
                        'h3_count' => ['type' => 'integer'],
                        'h4_count' => ['type' => 'integer'],
                        'paragraph_count' => ['type' => 'integer'],
                        'image_count' => ['type' => 'integer'],
                        'internal_link_count' => ['type' => 'integer'],
                        'external_link_count' => ['type' => 'integer'],
                    ],
                ],
                'changes' => [
                    'type' => 'array',
                    'description' => 'Liste von Einzeländerungen: [{type: "added_h2", detail: "POS im Vergleich"}, {type: "rewritten_paragraph", detail: "Einleitung"}, ...].',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string', 'description' => 'z.B. added_h2, removed_h3, rewritten_paragraph, added_image, added_internal_link, updated_meta_title, updated_meta_description'],
                            'detail' => ['type' => 'string', 'description' => 'Beschreibung der Einzeländerung.'],
                        ],
                    ],
                ],
                'revised_at' => [
                    'type' => 'string',
                    'description' => 'Zeitpunkt der Änderung (ISO 8601). Standard: jetzt.',
                ],
            ],
            'required' => ['content_brief_board_id', 'summary'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $briefId = $arguments['content_brief_board_id'] ?? null;
            if (!$briefId) {
                return ToolResult::error('VALIDATION_ERROR', 'content_brief_board_id ist erforderlich.');
            }

            $summary = $arguments['summary'] ?? null;
            if (!$summary) {
                return ToolResult::error('VALIDATION_ERROR', 'summary ist erforderlich.');
            }

            $brief = BrandsContentBriefBoard::with('brand')->find($briefId);
            if (!$brief) {
                return ToolResult::error('NOT_FOUND', 'Content Brief Board nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $brief);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Keine Berechtigung für dieses Content Brief Board.');
            }

            // Revision-Typ gegen Lookup validieren
            $revisionType = $arguments['revision_type'] ?? 'optimization';
            if (!BrandsContentBriefBoard::isValidLookupValue('revision_type', $revisionType, $brief->team_id)) {
                $allowed = BrandsContentBriefBoard::getAllowedValues('revision_type', $brief->team_id);
                if (!empty($allowed) && !in_array($revisionType, $allowed)) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültiger revision_type. Erlaubt: ' . implode(', ', $allowed));
                }
            }

            $revision = BrandsContentBriefRevision::create([
                'content_brief_board_id' => $brief->id,
                'revision_type' => $revisionType,
                'summary' => $summary,
                'metrics_before' => $arguments['metrics_before'] ?? null,
                'metrics_after' => $arguments['metrics_after'] ?? null,
                'changes' => $arguments['changes'] ?? null,
                'user_id' => $context->user->id,
                'revised_at' => $arguments['revised_at'] ?? now(),
            ]);

            $revision->load('user');

            $response = [
                'id' => $revision->id,
                'uuid' => $revision->uuid,
                'content_brief_board_id' => $brief->id,
                'brief_name' => $brief->name,
                'revision_type' => $revision->revision_type,
                'summary' => $revision->summary,
                'metrics_before' => $revision->metrics_before,
                'metrics_after' => $revision->metrics_after,
                'metrics_delta' => $revision->metrics_delta,
                'changes' => $revision->changes,
                'user_name' => $revision->user?->name,
                'revised_at' => $revision->revised_at->toIso8601String(),
                'message' => "Revision für '{$brief->name}' dokumentiert: {$revision->summary}",
            ];

            return ToolResult::success($response);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Revision: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_brief', 'revision', 'create', 'changelog'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
