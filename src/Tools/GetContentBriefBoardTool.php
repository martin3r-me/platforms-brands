<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsContentBriefBoard;
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

            $board = BrandsContentBriefBoard::with(['brand', 'user', 'team', 'seoBoard'])
                ->find($arguments['id']);

            if (!$board) {
                return ToolResult::error('CONTENT_BRIEF_BOARD_NOT_FOUND', 'Das angegebene Content Brief Board wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $board);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Content Brief Board (Policy).');
            }

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
