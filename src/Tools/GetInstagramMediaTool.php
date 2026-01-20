<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Brands\Models\BrandsInstagramMedia;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen Instagram Media
 */
class GetInstagramMediaTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.instagram_media_item.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/instagram_media/{id} - Ruft ein einzelnes Instagram Media (Post, Story, Reel) ab. REST-Parameter: id (required, integer) - Instagram Media-ID. Nutze "brands.instagram_media.GET" um verfügbare Instagram Media-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Instagram Media. Nutze "brands.instagram_media.GET" um verfügbare Instagram Media-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Instagram Media-ID ist erforderlich. Nutze "brands.instagram_media.GET" um Instagram Media zu finden.');
            }

            // InstagramMedia holen
            $media = BrandsInstagramMedia::with(['instagramAccount', 'user'])
                ->find($arguments['id']);

            if (!$media) {
                return ToolResult::error('INSTAGRAM_MEDIA_NOT_FOUND', 'Das angegebene Instagram Media wurde nicht gefunden. Nutze "brands.instagram_media.GET" um alle verfügbaren Instagram Media zu sehen.');
            }

            // Policy prüfen über Instagram Account
            try {
                Gate::forUser($context->user)->authorize('view', $media->instagramAccount);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf dieses Instagram Media (Policy).');
            }

            $data = [
                'id' => $media->id,
                'uuid' => $media->uuid,
                'external_id' => $media->external_id,
                'caption' => $media->caption,
                'text' => $media->caption, // Alias für einfacheren Zugriff
                'content' => $media->caption, // Alias für einfacheren Zugriff
                'media_type' => $media->media_type,
                'media_url' => $media->media_url,
                'permalink' => $media->permalink,
                'thumbnail_url' => $media->thumbnail_url,
                'timestamp' => $media->timestamp?->toIso8601String(),
                'like_count' => $media->like_count,
                'comments_count' => $media->comments_count,
                'is_story' => $media->is_story,
                'insights_available' => $media->insights_available,
                'instagram_account_id' => $media->instagram_account_id,
                'instagram_account_username' => $media->instagramAccount->username ?? null,
                'created_at' => $media->created_at->toIso8601String(),
                'updated_at' => $media->updated_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Instagram Media: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'instagram_media', 'get', 'social_media', 'posts'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
