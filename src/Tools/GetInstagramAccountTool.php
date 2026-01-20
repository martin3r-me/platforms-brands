<?php

namespace Platform\Brands\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen Instagram Accounts
 */
class GetInstagramAccountTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'brands.instagram_account.GET';
    }

    public function getDescription(): string
    {
        return 'GET /brands/instagram_accounts/{id} - Ruft einen einzelnen Instagram Account ab. REST-Parameter: id (required, integer) - Instagram Account-ID. Nutze "brands.instagram_accounts.GET" um verfügbare Instagram Account-IDs zu sehen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST-Parameter (required): ID des Instagram Accounts. Nutze "brands.instagram_accounts.GET" um verfügbare Instagram Account-IDs zu sehen.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Instagram Account-ID ist erforderlich. Nutze "brands.instagram_accounts.GET" um Instagram Accounts zu finden.');
            }

            // InstagramAccount holen
            $instagramAccount = IntegrationsInstagramAccount::with(['user', 'facebookPage'])
                ->find($arguments['id']);

            if (!$instagramAccount) {
                return ToolResult::error('INSTAGRAM_ACCOUNT_NOT_FOUND', 'Der angegebene Instagram Account wurde nicht gefunden. Nutze "brands.instagram_accounts.GET" um alle verfügbaren Instagram Accounts zu sehen.');
            }

            // Policy prüfen
            try {
                Gate::forUser($context->user)->authorize('view', $instagramAccount);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Instagram Account (Policy).');
            }

            $data = [
                'id' => $instagramAccount->id,
                'uuid' => $instagramAccount->uuid,
                'username' => $instagramAccount->username,
                'description' => $instagramAccount->description,
                'external_id' => $instagramAccount->external_id,
                'facebook_page_id' => $instagramAccount->facebook_page_id,
                'facebook_page_name' => $instagramAccount->facebookPage->name ?? null,
                'user_id' => $instagramAccount->user_id,
                'created_at' => $instagramAccount->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Instagram Accounts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['brands', 'instagram_account', 'get', 'social_media'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
