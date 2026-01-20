<?php

namespace Platform\Brands\Services;

use Platform\Brands\Models\BrandsBrand;
use Platform\Integrations\Models\IntegrationsInstagramAccount;
use Platform\Integrations\Services\IntegrationsInstagramAccountService as CoreInstagramAccountService;
use Illuminate\Support\Facades\Log;

/**
 * Service für Instagram Accounts Management (Brands-spezifische Wrapper)
 */
class InstagramAccountService
{
    protected CoreInstagramAccountService $coreService;

    public function __construct(CoreInstagramAccountService $coreService)
    {
        $this->coreService = $coreService;
    }

    /**
     * Ruft Instagram Accounts für eine Brand ab und speichert sie
     */
    public function syncInstagramAccounts(BrandsBrand $brand): array
    {
        $metaConnection = $brand->metaConnection();
        
        if (!$metaConnection) {
            throw new \Exception('Keine Meta-Connection für diese Marke gefunden. Bitte verknüpfe zuerst die Marke mit Meta.');
        }

        // Core-Service aufrufen
        $syncedAccounts = $this->coreService->syncInstagramAccountsForUser($metaConnection);

        // TODO: Verknüpfung zur Brand implementieren, wenn benötigt
        foreach ($syncedAccounts as $instagramAccount) {
            Log::info('Instagram Account synced for user', [
                'instagram_account_id' => $instagramAccount->id,
                'user_id' => $metaConnection->owner_user_id,
            ]);
        }

        return $syncedAccounts;
    }
}
