<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Platform\Brands\Models\BrandsInstagramMedia;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von Instagram Media eines Instagram Accounts
 */
class ListInstagramMediaTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'brands.instagram_media.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/instagram_accounts/{instagram_account_id}/instagram_media - Listet Instagram Media (Posts, Stories, Reels) eines Instagram Accounts auf. REST-Parameter: instagram_account_id (required, integer) - Instagram Account-ID. filters (optional, array) - Filter-Array. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'instagram_account_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (required): ID des Instagram Accounts. Nutze "brands.instagram_accounts.GET" um Instagram Accounts zu finden.'
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

            $instagramAccountId = $arguments['instagram_account_id'] ?? null;
            if (!$instagramAccountId) {
                return ToolResult::error('VALIDATION_ERROR', 'instagram_account_id ist erforderlich.');
            }

            $instagramAccount = IntegrationsInstagramAccount::find($instagramAccountId);
            if (!$instagramAccount) {
                return ToolResult::error('INSTAGRAM_ACCOUNT_NOT_FOUND', 'Der angegebene Instagram Account wurde nicht gefunden.');
            }

            // Policy prüfen
            if (!Gate::forUser($context->user)->allows('view', $instagramAccount)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Instagram Account.');
            }
            
            // Query aufbauen - Instagram Media
            $query = BrandsInstagramMedia::query()
                ->where('instagram_account_id', $instagramAccountId)
                ->with(['instagramAccount', 'user']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'caption', 'media_type', 'is_story', 'timestamp', 'created_at', 'updated_at'
            ]);
            
            // Standard-Suche anwenden
            $this->applyStandardSearch($query, $arguments, ['caption', 'external_id']);
            
            // Standard-Sortierung anwenden
            $this->applyStandardSort($query, $arguments, [
                'timestamp', 'created_at', 'updated_at', 'like_count', 'comments_count'
            ], 'timestamp', 'desc');
            
            // Standard-Pagination anwenden
            $this->applyStandardPagination($query, $arguments);

            $mediaItems = $query->get();

            // Media formatieren
            $mediaList = $mediaItems->map(function($media) {
                return [
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
                ];
            })->values()->toArray();

            return ToolResult::success([
                'instagram_media' => $mediaList,
                'count' => count($mediaList),
                'instagram_account_id' => $instagramAccountId,
                'instagram_account_username' => $instagramAccount->username,
                'message' => count($mediaList) > 0 
                    ? count($mediaList) . ' Instagram Media gefunden für Account "' . $instagramAccount->username . '".'
                    : 'Keine Instagram Media gefunden für Account "' . $instagramAccount->username . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Instagram Media: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'instagram_media', 'list', 'social_media', 'posts'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
