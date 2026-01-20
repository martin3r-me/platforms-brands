<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsSocialCard;
use Platform\Brands\Models\BrandsContentBoardBlock;
use Platform\Brands\Models\BrandsFacebookPost;
use Platform\Brands\Models\BrandsInstagramMedia;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen von Inhalten, Captions und Texten aus Social Cards, Content Board Blocks, Facebook Posts und Instagram Media
 */
class GetContentTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.content.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/content - Ruft Inhalte, Captions und Texte ab. REST-Parameter: type (required, string) - Typ: "social_card", "content_board_block", "facebook_post" oder "instagram_media". id (required, integer) - ID des Elements. Gibt alle verfügbaren Inhalte zurück (body_md, description, title, name, caption, message, etc.).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => ['social_card', 'content_board_block', 'facebook_post', 'instagram_media'],
                    'description' => 'Typ des Elements (ERFORDERLICH): "social_card" für Social Cards, "content_board_block" für Content Board Blocks, "facebook_post" für Facebook Posts oder "instagram_media" für Instagram Media.'
                ],
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID des Elements (ERFORDERLICH). Nutze "brands.social_cards.GET", "brands.content_board_blocks.GET", "brands.facebook_posts.GET" oder "brands.instagram_media.GET" um IDs zu finden.'
                ],
            ],
            'required' => ['type', 'id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $type = $arguments['type'] ?? null;
            $id = $arguments['id'] ?? null;

            if (!$type || !$id) {
                return ToolResult::error('VALIDATION_ERROR', 'type und id sind erforderlich.');
            }

            $content = null;
            $contentData = [];

            switch ($type) {
                case 'social_card':
                    $content = BrandsSocialCard::with(['socialBoard', 'slot', 'user', 'team'])
                        ->find($id);
                    
                    if (!$content) {
                        return ToolResult::error('SOCIAL_CARD_NOT_FOUND', 'Die angegebene Social Card wurde nicht gefunden.');
                    }

                    // Policy prüfen
                    try {
                        Gate::forUser($context->user)->authorize('view', $content);
                    } catch (AuthorizationException $e) {
                        return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Social Card (Policy).');
                    }

                    $contentData = [
                        'type' => 'social_card',
                        'id' => $content->id,
                        'uuid' => $content->uuid,
                        'title' => $content->title,
                        'body_md' => $content->body_md,
                        'description' => $content->description,
                        'caption' => $content->body_md, // Alias für body_md
                        'text' => $content->body_md, // Alias für body_md
                        'content' => $content->body_md, // Alias für body_md
                        'social_board_id' => $content->social_board_id,
                        'social_board_name' => $content->socialBoard->name,
                        'slot_id' => $content->social_board_slot_id,
                        'slot_name' => $content->slot->name ?? null,
                        'created_at' => $content->created_at->toIso8601String(),
                        'updated_at' => $content->updated_at->toIso8601String(),
                    ];
                    break;

                case 'content_board_block':
                    $content = BrandsContentBoardBlock::with(['row.section.contentBoard', 'user', 'team'])
                        ->find($id);
                    
                    if (!$content) {
                        return ToolResult::error('CONTENT_BOARD_BLOCK_NOT_FOUND', 'Der angegebene Content Board Block wurde nicht gefunden.');
                    }

                    $contentBoard = $content->row->section->contentBoard;

                    // Policy prüfen
                    try {
                        Gate::forUser($context->user)->authorize('view', $contentBoard);
                    } catch (AuthorizationException $e) {
                        return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Content Board Block (Policy).');
                    }

                    $contentData = [
                        'type' => 'content_board_block',
                        'id' => $content->id,
                        'uuid' => $content->uuid,
                        'name' => $content->name,
                        'description' => $content->description,
                        'text' => $content->description, // Alias für description
                        'content' => $content->description, // Alias für description
                        'span' => $content->span,
                        'row_id' => $content->row_id,
                        'content_board_id' => $contentBoard->id,
                        'content_board_name' => $contentBoard->name,
                        'created_at' => $content->created_at->toIso8601String(),
                        'updated_at' => $content->updated_at->toIso8601String(),
                    ];
                    break;

                case 'facebook_post':
                    $content = BrandsFacebookPost::with(['facebookPage', 'user'])
                        ->find($id);
                    
                    if (!$content) {
                        return ToolResult::error('FACEBOOK_POST_NOT_FOUND', 'Der angegebene Facebook Post wurde nicht gefunden.');
                    }

                    // Policy prüfen über Facebook Page
                    try {
                        Gate::forUser($context->user)->authorize('view', $content->facebookPage);
                    } catch (AuthorizationException $e) {
                        return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Facebook Post (Policy).');
                    }

                    $contentData = [
                        'type' => 'facebook_post',
                        'id' => $content->id,
                        'uuid' => $content->uuid,
                        'message' => $content->message,
                        'story' => $content->story,
                        'caption' => $content->message ?? $content->story, // Alias für einfacheren Zugriff
                        'text' => $content->message ?? $content->story, // Alias für einfacheren Zugriff
                        'content' => $content->message ?? $content->story, // Alias für einfacheren Zugriff
                        'post_type' => $content->type, // Post-Typ (photo, video, status, etc.)
                        'media_url' => $content->media_url,
                        'permalink_url' => $content->permalink_url,
                        'published_at' => $content->published_at?->toIso8601String(),
                        'like_count' => $content->like_count,
                        'comment_count' => $content->comment_count,
                        'share_count' => $content->share_count,
                        'facebook_page_id' => $content->facebook_page_id,
                        'facebook_page_name' => $content->facebookPage->name ?? null,
                        'created_at' => $content->created_at->toIso8601String(),
                        'updated_at' => $content->updated_at->toIso8601String(),
                    ];
                    break;

                case 'instagram_media':
                    $content = BrandsInstagramMedia::with(['instagramAccount', 'user'])
                        ->find($id);
                    
                    if (!$content) {
                        return ToolResult::error('INSTAGRAM_MEDIA_NOT_FOUND', 'Das angegebene Instagram Media wurde nicht gefunden.');
                    }

                    // Policy prüfen über Instagram Account
                    try {
                        Gate::forUser($context->user)->authorize('view', $content->instagramAccount);
                    } catch (AuthorizationException $e) {
                        return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Instagram Media (Policy).');
                    }

                    $contentData = [
                        'type' => 'instagram_media',
                        'id' => $content->id,
                        'uuid' => $content->uuid,
                        'caption' => $content->caption,
                        'text' => $content->caption, // Alias für einfacheren Zugriff
                        'content' => $content->caption, // Alias für einfacheren Zugriff
                        'media_type' => $content->media_type,
                        'media_url' => $content->media_url,
                        'permalink' => $content->permalink,
                        'thumbnail_url' => $content->thumbnail_url,
                        'timestamp' => $content->timestamp?->toIso8601String(),
                        'like_count' => $content->like_count,
                        'comments_count' => $content->comments_count,
                        'is_story' => $content->is_story,
                        'instagram_account_id' => $content->instagram_account_id,
                        'instagram_account_username' => $content->instagramAccount->username ?? null,
                        'created_at' => $content->created_at->toIso8601String(),
                        'updated_at' => $content->updated_at->toIso8601String(),
                    ];
                    break;

                default:
                    return ToolResult::error('INVALID_TYPE', 'Ungültiger Typ. Erlaubt: "social_card", "content_board_block", "facebook_post" oder "instagram_media".');
            }

            return ToolResult::success($contentData);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Inhalte: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'content', 'caption', 'text', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
