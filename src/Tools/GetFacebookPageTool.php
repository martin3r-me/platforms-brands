<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Integrations\Models\IntegrationsFacebookPage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen einer einzelnen Facebook Page
 */
class GetFacebookPageTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.facebook_page.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/facebook_pages/{id} - Ruft eine einzelne Facebook Page ab. REST-Parameter: id (required, integer) - Facebook Page-ID. Nutze "brands.facebook_pages.GET" um verfügbare Facebook Page-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID der Facebook Page. Nutze "brands.facebook_pages.GET" um verfügbare Facebook Page-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Facebook Page-ID ist erforderlich. Nutze "brands.facebook_pages.GET" um Facebook Pages zu finden.');
            }

            // FacebookPage holen
            $facebookPage = IntegrationsFacebookPage::with(['user', 'instagramAccounts'])
                ->find($arguments['id']);

            if (!$facebookPage) {
                return ToolResult::error('FACEBOOK_PAGE_NOT_FOUND', 'Die angegebene Facebook Page wurde nicht gefunden. Nutze "brands.facebook_pages.GET" um alle verfügbaren Facebook Pages zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $facebookPage);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Facebook Page (Policy).');
            }

            $data = [
                'id' => $facebookPage->id,
                'uuid' => $facebookPage->uuid,
                'name' => $facebookPage->name,
                'description' => $facebookPage->description,
                'external_id' => $facebookPage->external_id,
                'user_id' => $facebookPage->user_id,
                'instagram_accounts_count' => $facebookPage->instagramAccounts->count(),
                'created_at' => $facebookPage->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Facebook Page: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'facebook_page', 'get', 'social_media'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
