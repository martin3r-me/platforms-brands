<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsFacebookPost;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen Facebook Posts
 */
class GetFacebookPostTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.facebook_post.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/facebook_posts/{id} - Ruft einen einzelnen Facebook Post ab. REST-Parameter: id (required, integer) - Facebook Post-ID. Nutze "brands.facebook_posts.GET" um verfügbare Facebook Post-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Facebook Posts. Nutze "brands.facebook_posts.GET" um verfügbare Facebook Post-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Facebook Post-ID ist erforderlich. Nutze "brands.facebook_posts.GET" um Facebook Posts zu finden.');
            }

            // FacebookPost holen
            $post = BrandsFacebookPost::with(['facebookPage', 'user'])
                ->find($arguments['id']);

            if (!$post) {
                return ToolResult::error('FACEBOOK_POST_NOT_FOUND', 'Der angegebene Facebook Post wurde nicht gefunden. Nutze "brands.facebook_posts.GET" um alle verfügbaren Facebook Posts zu sehen.');
            }

            // Policy prüfen über Facebook Page
            try {
                Gate::forUser($context->user)->authorize('view', $post->facebookPage);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Facebook Post (Policy).');
            }

            $data = [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'external_id' => $post->external_id,
                'message' => $post->message,
                'story' => $post->story,
                'caption' => $post->message ?? $post->story, // Alias für einfacheren Zugriff
                'text' => $post->message ?? $post->story, // Alias für einfacheren Zugriff
                'content' => $post->message ?? $post->story, // Alias für einfacheren Zugriff
                'type' => $post->type,
                'media_url' => $post->media_url,
                'permalink_url' => $post->permalink_url,
                'published_at' => $post->published_at?->toIso8601String(),
                'scheduled_publish_time' => $post->scheduled_publish_time?->toIso8601String(),
                'status' => $post->status,
                'like_count' => $post->like_count,
                'comment_count' => $post->comment_count,
                'share_count' => $post->share_count,
                'facebook_page_id' => $post->facebook_page_id,
                'facebook_page_name' => $post->facebookPage->name ?? null,
                'created_at' => $post->created_at->toIso8601String(),
                'updated_at' => $post->updated_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Facebook Posts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'facebook_post', 'get', 'social_media', 'posts'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
