<?php

namespace Platform\Brands\Policies;

use Platform\Core\Models\User;
use Platform\Integrations\Models\IntegrationsInstagramAccount;

class InstagramAccountPolicy
{
    /**
     * Darf der User diesen Instagram Account sehen?
     */
    public function view(User $user, IntegrationsInstagramAccount $instagramAccount): bool
    {
        // User muss Owner sein
        return $instagramAccount->user_id === $user->id;
    }

    /**
     * Darf der User diesen Instagram Account bearbeiten?
     */
    public function update(User $user, IntegrationsInstagramAccount $instagramAccount): bool
    {
        // User muss Owner sein
        return $instagramAccount->user_id === $user->id;
    }

    /**
     * Darf der User diesen Instagram Account löschen?
     */
    public function delete(User $user, IntegrationsInstagramAccount $instagramAccount): bool
    {
        // User muss Owner sein
        return $instagramAccount->user_id === $user->id;
    }

    /**
     * Darf der User einen Instagram Account erstellen?
     */
    public function create(User $user): bool
    {
        // Jeder User kann Instagram Accounts erstellen
        return true;
    }
}
