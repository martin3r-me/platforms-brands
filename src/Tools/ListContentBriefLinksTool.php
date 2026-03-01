<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
use Platform\Brands\Models\BrandsContentBriefLink;
use Illuminate\Support\Facades\Gate;

class ListContentBriefLinksTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content_brief_links.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content_brief_links - Listet Verlinkungen zwischen Content Briefs auf. REST-Parameter: content_brief_id (optional, integer) - Filtert auf ein bestimmtes Content Brief (ein- und ausgehende Links). brand_id (optional, integer) - Filtert auf alle Links innerhalb einer Marke. link_type (optional, string) - Filtert auf einen bestimmten Link-Typ.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_brief_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Filtert auf ein bestimmtes Content Brief (zeigt ein- und ausgehende Links).'
                ],
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Filtert auf alle Links innerhalb einer Marke.'
                ],
                'link_type' => [
                    'type' => 'string',
                    'enum' => ['pillar_to_cluster', 'cluster_to_pillar', 'related', 'see_also'],
                    'description' => 'Optional: Filtert auf einen bestimmten Link-Typ.'
                ],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $contentBriefId = $arguments['content_brief_id'] ?? null;
            $brandId = $arguments['brand_id'] ?? null;
            $linkType = $arguments['link_type'] ?? null;

            if (!$contentBriefId && !$brandId) {
                return ToolResult::error('VALIDATION_ERROR', 'Entweder content_brief_id oder brand_id muss angegeben werden.');
            }

            // Validate link_type if provided
            if ($linkType && !array_key_exists($linkType, BrandsContentBriefLink::LINK_TYPES)) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültiger link_type. Erlaubt: ' . implode(', ', array_keys(BrandsContentBriefLink::LINK_TYPES)));
            }

            $query = BrandsContentBriefLink::with(['sourceContentBrief', 'targetContentBrief']);

            if ($contentBriefId) {
                $board = BrandsContentBriefBoard::find($contentBriefId);
                if (!$board) {
                    return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief wurde nicht gefunden.');
                }

                $query->where(function ($q) use ($contentBriefId) {
                    $q->where('source_content_brief_id', $contentBriefId)
                      ->orWhere('target_content_brief_id', $contentBriefId);
                });
            }

            if ($brandId) {
                $boardIds = BrandsContentBriefBoard::where('brand_id', $brandId)->pluck('id');
                $query->where(function ($q) use ($boardIds) {
                    $q->whereIn('source_content_brief_id', $boardIds)
                      ->orWhereIn('target_content_brief_id', $boardIds);
                });
            }

            if ($linkType) {
                $query->where('link_type', $linkType);
            }

            $links = $query->orderBy('created_at', 'desc')->get();

            $linksList = $links->map(function ($link) {
                return [
                    'id' => $link->id,
                    'source_content_brief_id' => $link->source_content_brief_id,
                    'source_content_brief_name' => $link->sourceContentBrief->name,
                    'source_content_type' => $link->sourceContentBrief->content_type,
                    'target_content_brief_id' => $link->target_content_brief_id,
                    'target_content_brief_name' => $link->targetContentBrief->name,
                    'target_content_type' => $link->targetContentBrief->content_type,
                    'link_type' => $link->link_type,
                    'link_type_label' => BrandsContentBriefLink::LINK_TYPES[$link->link_type] ?? $link->link_type,
                    'anchor_hint' => $link->anchor_hint,
                    'created_at' => $link->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'links' => $linksList,
                'count' => count($linksList),
                'message' => count($linksList) > 0
                    ? count($linksList) . ' Content Brief Link(s) gefunden.'
                    : 'Keine Content Brief Links gefunden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Content Brief Links: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content_brief_link', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
