<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefKeywordCluster;
use Platform\Brands\Models\BrandsContentBriefLink;
use Platform\Brands\Models\BrandsContentBriefSection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetContentBriefBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_brief_boards/{id} - Ruft ein einzelnes Content Brief Board ab. REST-Parameter: id (required, integer) - Content Brief Board-ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Content Brief Boards.'
                ]
            ],
            'required' => ['id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Content Brief Board-ID ist erforderlich.');
            }

            $board = BrandsContentBriefBoard::with([
                    'brand', 'user', 'team', 'seoBoard',
                    'outgoingLinks.targetContentBrief', 'incomingLinks.sourceContentBrief',
                    'briefKeywordClusters.keywordCluster.keywords', 'briefKeywordClusters.keywordCluster.seoBoard',
                    'sections',
                ])
                ->find($arguments['id']);

            if (!$board) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Content Brief Board (Policy).');
            }

            $outgoingLinks = $board->outgoingLinks->map(function ($link) {
                return [
                    'id' => $link->id,
                    'target_content_brief_id' => $link->target_content_brief_id,
                    'target_content_brief_name' => $link->targetContentBrief->name,
                    'target_content_type' => $link->targetContentBrief->content_type,
                    'link_type' => $link->link_type,
                    'link_type_label' => BrandsContentBriefLink::LINK_TYPES[$link->link_type] ?? $link->link_type,
                    'anchor_hint' => $link->anchor_hint,
                ];
            })->values()->toArray();

            $incomingLinks = $board->incomingLinks->map(function ($link) {
                return [
                    'id' => $link->id,
                    'source_content_brief_id' => $link->source_content_brief_id,
                    'source_content_brief_name' => $link->sourceContentBrief->name,
                    'source_content_type' => $link->sourceContentBrief->content_type,
                    'link_type' => $link->link_type,
                    'link_type_label' => BrandsContentBriefLink::LINK_TYPES[$link->link_type] ?? $link->link_type,
                    'anchor_hint' => $link->anchor_hint,
                ];
            })->values()->toArray();

            $keywordClusters = $board->briefKeywordClusters
                ->sortBy(function ($link) {
                    return match ($link->role) {
                        'primary' => 0,
                        'secondary' => 1,
                        default => 2,
                    };
                })
                ->map(function ($link) {
                    $cluster = $link->keywordCluster;
                    $keywords = $cluster->keywords->map(function ($kw) {
                        return [
                            'id' => $kw->id,
                            'keyword' => $kw->keyword,
                            'search_volume' => $kw->search_volume,
                            'keyword_difficulty' => $kw->keyword_difficulty,
                            'cpc_cents' => $kw->cpc_cents,
                            'search_intent' => $kw->search_intent,
                            'position' => $kw->position,
                        ];
                    })->values()->toArray();

                    return [
                        'id' => $link->id,
                        'seo_keyword_cluster_id' => $link->seo_keyword_cluster_id,
                        'cluster_name' => $cluster->name,
                        'cluster_color' => $cluster->color,
                        'seo_board_id' => $cluster->seo_board_id,
                        'seo_board_name' => $cluster->seoBoard?->name,
                        'role' => $link->role,
                        'role_label' => BrandsContentBriefKeywordCluster::ROLES[$link->role] ?? $link->role,
                        'keywords' => $keywords,
                        'keyword_count' => count($keywords),
                        'total_search_volume' => $cluster->keywords->sum('search_volume'),
                        'avg_keyword_difficulty' => $cluster->keywords->avg('keyword_difficulty')
                            ? round($cluster->keywords->avg('keyword_difficulty'), 1)
                            : null,
                    ];
                })->values()->toArray();

            $sections = $board->sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'order' => $section->order,
                    'heading' => $section->heading,
                    'heading_level' => $section->heading_level,
                    'heading_level_label' => BrandsContentBriefSection::HEADING_LEVELS[$section->heading_level] ?? $section->heading_level,
                    'description' => $section->description,
                    'target_keywords' => $section->target_keywords,
                    'notes' => $section->notes,
                ];
            })->values()->toArray();

            $data = [
                'id' => $board->id,
                'uuid' => $board->uuid,
                'name' => $board->name,
                'description' => $board->description,
                'content_type' => $board->content_type,
                'content_type_label' => BrandsContentBriefBoard::CONTENT_TYPES[$board->content_type] ?? $board->content_type,
                'search_intent' => $board->search_intent,
                'search_intent_label' => BrandsContentBriefBoard::SEARCH_INTENTS[$board->search_intent] ?? $board->search_intent,
                'status' => $board->status,
                'status_label' => BrandsContentBriefBoard::STATUSES[$board->status] ?? $board->status,
                'target_slug' => $board->target_slug,
                'target_word_count' => $board->target_word_count,
                'brand_id' => $board->brand_id,
                'brand_name' => $board->brand->name,
                'seo_board_id' => $board->seo_board_id,
                'seo_board_name' => $board->seoBoard?->name,
                'team_id' => $board->team_id,
                'user_id' => $board->user_id,
                'done' => $board->done,
                'done_at' => $board->done_at?->toIso8601String(),
                'outgoing_links' => $outgoingLinks,
                'incoming_links' => $incomingLinks,
                'keyword_clusters' => $keywordClusters,
                'sections' => $sections,
                'created_at' => $board->created_at->toIso8601String(),
                'updated_at' => $board->updated_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Content Brief Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_brief_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
