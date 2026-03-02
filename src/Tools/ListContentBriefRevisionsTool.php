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

class ListContentBriefRevisionsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_revisions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_brief_boards/{id}/revisions - Listet alle Revisionen eines Content Briefs chronologisch auf. Zeigt was wann geändert wurde, inkl. Metriken-Deltas. Ideal zur Korrelation mit Ranking-Entwicklung (brands.content_brief_rankings.GET mode="history").';
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
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Max. Einträge. Standard: 50.',
                ],
            ],
            'required' => ['content_brief_board_id'],
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

            $brief = BrandsContentBriefBoard::with('brand')->find($briefId);
            if (!$brief) {
                return ToolResult::error('NOT_FOUND', 'Content Brief Board nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $brief);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff auf dieses Content Brief Board.');
            }

            $limit = min($arguments['limit'] ?? 50, 200);

            $revisions = BrandsContentBriefRevision::where('content_brief_board_id', $brief->id)
                ->with('user')
                ->orderByDesc('revised_at')
                ->limit($limit)
                ->get();

            $data = $revisions->map(function ($r) {
                return [
                    'id' => $r->id,
                    'uuid' => $r->uuid,
                    'revision_type' => $r->revision_type,
                    'summary' => $r->summary,
                    'metrics_delta' => $r->metrics_delta,
                    'changes_count' => $r->changes ? count($r->changes) : 0,
                    'user_name' => $r->user?->name,
                    'revised_at' => $r->revised_at->toIso8601String(),
                ];
            })->toArray();

            return ToolResult::success([
                'brief_id' => $brief->id,
                'brief_name' => $brief->name,
                'target_url' => $brief->target_url,
                'revisions' => $data,
                'total' => count($data),
                'message' => count($data) > 0
                    ? count($data) . " Revision(en) für '{$brief->name}' gefunden."
                    : "Noch keine Revisionen für '{$brief->name}' dokumentiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Revisionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_brief', 'revision', 'list', 'changelog'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
