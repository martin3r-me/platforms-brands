<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Services\ContentBriefRankingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class TrackContentBriefRankingsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_rankings.TRACK';
    }

    public function getDescription(): string
    {
        return 'POST /brands/content_brief_boards/{id}/rankings/track - Startet manuelles Ranking-Tracking für ein Content Brief Board. Nutzt DataForSEO SERP API um zu prüfen, wie die target_url für jedes verknüpfte Keyword rankt. Achtung: Verursacht API-Kosten (~10 Cents pro Keyword). Parameter: content_brief_board_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Content Brief Boards (ERFORDERLICH). Muss eine target_url gesetzt haben.',
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
                Gate::forUser($context->user)->authorize('update', $brief);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Keine Berechtigung für dieses Content Brief Board.');
            }

            if (!$brief->target_url) {
                return ToolResult::error('VALIDATION_ERROR', "Content Brief '{$brief->name}' hat keine target_url gesetzt. Bitte zuerst eine URL setzen.");
            }

            /** @var ContentBriefRankingService $service */
            $service = resolve(ContentBriefRankingService::class);

            $result = $service->trackBriefRankings($brief, $context->user);

            if (isset($result['error'])) {
                return ToolResult::error('TRACKING_ERROR', $result['error']);
            }

            return ToolResult::success([
                'brief_id' => $brief->id,
                'brief_name' => $brief->name,
                'target_url' => $brief->target_url,
                'tracked' => $result['tracked'],
                'matched' => $result['matched'],
                'not_found' => $result['not_found'],
                'cost_cents' => $result['cost_cents'],
                'message' => "{$result['tracked']} Keywords getrackt für '{$brief->name}'. {$result['matched']} URLs matchen, {$result['not_found']} nicht im SERP gefunden. Kosten: {$result['cost_cents']} Cents.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Ranking-Tracking: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['brands', 'content_brief', 'rankings', 'seo', 'tracking', 'dataforseo'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
            'side_effects' => ['creates', 'api_call'],
        ];
    }
}
