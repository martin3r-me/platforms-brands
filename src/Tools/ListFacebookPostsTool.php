<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Integrations\Models\IntegrationsFacebookPage;
use Platform\Brands\Models\BrandsFacebookPost;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von Facebook Posts einer Facebook Page
 */
class ListFacebookPostsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.facebook_posts.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/facebook_pages/{facebook_page_id}/facebook_posts - Listet Facebook Posts einer Facebook Page auf. REST-Parameter: facebook_page_id (required, integer) - Facebook Page-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'facebook_page_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID der Facebook Page. Nutze "brands.facebook_pages.GET" um Facebook Pages zu finden.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $facebookPageId = $arguments['facebook_page_id'] ?? null;
            if (!$facebookPageId) {
                return ToolResult::error('VALIDATION_ERROR', 'facebook_page_id ist erforderlich.');
            }

            $facebookPage = IntegrationsFacebookPage::find($facebookPageId);
            if (!$facebookPage) {
                return ToolResult::error('FACEBOOK_PAGE_NOT_FOUND', 'Die angegebene Facebook Page wurde nicht gefunden.');
            }

            // Policy prüfen
            if (!Gate::forUser($context->user)->allows('view', $facebookPage)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Facebook Page.');
            }
            
            // Query aufbauen - Facebook Posts
            $query = BrandsFacebookPost::query()
                ->where('facebook_page_id', $facebookPageId)
                ->with(['facebookPage', 'user']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'message', 'story', 'type', 'status', 'published_at', 'created_at', 'updated_at'
            ]);
            
            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['message', 'story', 'external_id']);
            
            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'published_at', 'created_at', 'updated_at', 'like_count', 'comment_count'
            ], 'published_at', 'desc');
            
            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            $posts = $query->get();

            // Posts formatieren
            $postsList = $posts->map(function($post) {
                return [
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
                ];
            })->values()->toArray();

            return ToolResult::success([
                'facebook_posts' => $postsList,
                'count' => count($postsList),
                'facebook_page_id' => $facebookPageId,
                'facebook_page_name' => $facebookPage->name,
                'message' => count($postsList) > 0 
                    ? count($postsList) . ' Facebook Post(s) gefunden für Page "' . $facebookPage->name . '".'
                    : 'Keine Facebook Posts gefunden für Page "' . $facebookPage->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Facebook Posts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'facebook_post', 'list', 'social_media', 'posts'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
